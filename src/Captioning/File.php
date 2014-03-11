<?php

namespace Captioning;

use Captioning\FileInterface;
use ForceUTF8\Encoding;

abstract class File implements FileInterface
{
    protected $cues;
    protected $filename;
    protected $encoding;

    protected $file_content;

    protected $stats;

    public function __construct($_filename = null, $_encoding = null)
    {
        if ($_filename !== null) {
            $this->setFilename($_filename);
        }
        
        if ($_encoding !== null) {
            $this->setEncoding($_encoding);
        }

        if ($this->getFilename() !== null) {
            $this->load();
            $this->parse();
        }

        $this->stats = array(
            'tooSlow'        => 0,
            'slowAcceptable' => 0,
            'aBitSlow'       => 0,
            'goodSlow'       => 0,
            'perfect'        => 0,
            'goodFast'       => 0,
            'aBitFast'       => 0,
            'fastAcceptable' => 0,
            'tooFast'        => 0
        );
    }

    public function setFilename($_filename)
    {
        $this->filename = file_exists($_filename) ? $_filename : null;
    }

    public function setEncoding($_encoding)
    {
        $this->encoding = $_encoding;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getFileContent()
    {
        return $this->file_content;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function getCue($_index)
    {
        return isset($this->cues[$_index]) ? $this->cues[$_index] : false;
    }

    public function getCues()
    {
        return $this->cues;
    }
    
    public function getCuesCount()
    {
        return count($this->cues);
    }

    public function load($_filename = null)
    {
        if ($_filename === null) {
            $_filename = $this->filename;
        }

        if (!file_exists($_filename)) {
            throw new \Exception('File "'.$_filename.'" not found.');
        }

        if (!($this->file_content = file_get_contents($this->filename))) {
            throw new \Exception('Cound not read file content ('.$_filename.').');
        }

        $this->file_content .= "\n\n"; // fixes files missing blank lines at the end
        $this->encode();
    }

    protected function encode()
    {
        $this->file_content = Encoding::toUTF8($this->file_content);
    }

    /**
     * Searches a word/expression and returns ids of the matched entries
     *
     * @param string $_word
     * @param boolean $_case_sensitive
     * @param boolean $strict
     * @return array containing ids of entries
     */
    public function search($_word, $_case_sensitive = false, $_strict = false)
    {
        $list = array();
        $pattern = preg_quote($_word, '#');

        $pattern = str_replace(' ', '( |\r\n|\r|\n)', $pattern);

        if ($_strict) {
            $pattern = '($| |\r\n|\r|\n|\?|\!|\.|,  )'.$pattern.'(^| |\r\n|\r|\n|\?|\!|\.|,)';
        }

        $pattern = '#'.$pattern.'#';

        if (!$_case_sensitive) {
            $pattern .= 'i';
        }
        
        $i = 0;
        foreach ($this->cues as $cue) {
            if (preg_match($pattern, $cue->getText())) {
                $list[] = $i;
            }
            $i++;
        }

        return (count($list) > 0)?$list:-1;
    }

    public function getCueFromStart($_start)
    {
        $start = is_int($_start) ? $_start : self::tc2ms($_start);

        $prev_stop = 0;
        $res = false;
        $i = 0;
        foreach ($this->cues as $cue) {
            if (($start > $prev_stop && $start < $cue->getStart()) || ($start >= $cue->getStart() && $start < $cue->getStop())) {
                $res = $cue;
                break;
            }
            $prev_stop = $cue->getStop();
            $i++;
        }

        return $i;
    }

    /**
     * Add a cue
     *
     * @param mixed $_mixed An cue instance or a string representing the text
     * @param string $_start A timecode
     * @param string $_stop A timecode
     */
    public function addCue($_mixed, $_start = null, $_stop = null)
    {
        $fileClass = explode('\\', get_class($this));
        $fileFormat = explode('File', end($fileClass))[0];

        if (is_subclass_of($_mixed, __NAMESPACE__.'\Cue')) {
            $cueClass = explode('\\', get_class($_mixed));
            $cueFormat = explode('Cue', end($cueClass))[0];

            if ($cueFormat !== $fileFormat) {
                throw new \Exception("Can't add a $cueFormat cue in a $fileFormat file.");
            }
            $this->cues[] = $_mixed;

        } else {
            array_pop($fileClass);
            $cueClass = implode('\\', $fileClass).'\\'.$fileFormat.'Cue';
            $this->cues[] = new $cueClass($_start, $_stop, $_mixed);
        }

        return $this;
    }

    /**
     * Removes a cue
     *
     * @param int $_index
     */
    public function removeCue($_index)
    {
        if (isset($this->cues[$_index])) {
            unset($this->cues[$_index]);
        }
    }

    /**
     * Sorts cues
     */
    public function sortCues()
    {
        $tmp = array();

        $count = 0; // useful if 2 cues start at the same time code
        foreach ($this->cues as $cue) {
            $tmp[$cue->getStartMS().'.'.$count] = $cue;
            $count++;
        }

        ksort($tmp);

        $this->cues = array();
        foreach ($tmp as $cue) {
            $this->cues[] = $cue;
        }
    }

    /**
     * Converts timecodes based on the specified FPS ratio
     *
     * @param float $_old_fps
     * @param float $_new_fps
     */
    public function changeFPS($_old_fps, $_new_fps)
    {
        for ($i = 0; $i < $this->getCuesCount(); $i++) {
            $cue = $this->getCue($i);
            $old_start = $cue->getStart();
            $old_stop = $cue->getStop();

            $new_start = $old_start * ($_new_fps / $_old_fps);
            $new_stop = $old_stop * ($_new_fps / $_old_fps);

            $cue->setStart($new_start);
            $cue->setStop($new_stop);
        }
    }

    /**
     * Saves the file
     *
     * @param string $filename
     */
    public function save($filename = null)
    {
        if ($filename == null) {
            $filename = $this->filename;
        }
        
        $file_content = $this->file_content;
        if (strtolower($this->encoding) != 'utf-8') {
            $file_content = mb_convert_encoding($file_content, $this->encoding, 'UTF-8');
        }
               
        $res = file_put_contents($filename, $file_content);
        if (!$res) {
            throw new \Exception('Unable to save the file.');
        }
    }

    /**
     * Computes reading speed statistics 
     */
    public function calcStats(){
        $keys = array_keys($this->subs);
        $sub_count = sizeof($keys);

        for ($i = 0; $i < $this->getCuesCount(); $i++) {
            $rs = $this->getCue($i)->getReadingSpeed();

            if ($rs < 5) {
                $this->stats['tooSlow']++;
            } elseif($rs < 10) {
                $this->stats['slowAcceptable']++;
            } elseif($rs < 13) {
                $this->stats['aBitSlow']++;
            } elseif($rs < 15) {
                $this->stats['goodSlow']++;
            } elseif($rs < 23) {
                $this->stats['perfect']++;
            } elseif($rs < 27) {
                $this->stats['goodFast']++;
            } elseif($rs < 31) {
                $this->stats['aBitFast']++;
            } elseif($rs < 35) {
                $this->stats['fastAcceptable']++;
            } else{
                $this->stats['tooFast']++;
            }
        }
    }
}
