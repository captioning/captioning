<?php

namespace Captioning;

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

        return $this;
    }

    public function setEncoding($_encoding)
    {
        $this->encoding = $_encoding;

        return $this;
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

        return $this;
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

        return (count($list) > 0) ? $list : -1;
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

        return $this;
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

        return $this;
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

        return $this;
    }

    public function merge($_file)
    {
        if (!is_a($_file, get_class($this))) {
            throw new \Exception('Can\'t merge! Wrong type: '.end(explode('\\', get_class($_file))));
        } else {
            $this->cues = array_merge($this->cues, $_file->getCues());
            $this->sortCues();
        }

        return $this;
    }

    /**
    * Shifts a range of subtitles a specified amount of time.
    *
    * @param $_time The time to use (ms), which can be positive or negative.
    * @param int $_startIndex The subtitle index the range begins with.
    * @param int $_endIndex The subtitle index the range ends with.
    */
    public function shift($_time, $_startIndex = false, $_endIndex = false)
    {
        if (!is_int($_time)) {
            return false;
        }
        if ($_time == 0) {
            return true;
        }

        if (!$_startIndex) {
            $_startIndex = 0;
        }
        if (!$_endIndex) {
            $_endIndex = $this->getCuesCount() - 1;
        }
        
        $startCue = $this->getCue($_startIndex);
        $endCue = $this->getCue($_endIndex);
        
        //check subtitles do exist
        if (!$startCue || !$endCue) {
            return false;
        }
        
        for ($i = $_startIndex; $i < $_endIndex; $i++) {
                $cue = $this->getCue($i);
                $cue->shift($_time);
        }
        
        return true;
    }

    /**
     * Auto syncs a range of subtitles given their first and last correct times.
     * The subtitles are first shifted to the first subtitle's correct time, and then proportionally 
     * adjusted using the last subtitle's correct time.
     * 
     * Based on gnome-subtitles (https://git.gnome.org/browse/gnome-subtitles/)
     * 
     * @param int $_startIndex The subtitle index to start the adjustment with.
     * @param int $_startTime The correct start time for the first subtitle.
     * @param int $_endIndex The subtitle index to end the adjustment with.
     * @param int $_endTime The correct start time for the last subtitle.
     * @param bool $_syncLast Whether to sync the last subtitle.
     * @return bool Whether the subtitles could be adjusted
    */
    
    public function sync($_startIndex, $_startTime, $_endIndex, $_endTime, $_syncLast = true)
    {
        //set first and last subtitles index
        if (!$_startIndex) {
            $_startIndex = 0;
        }
        if (!$_endIndex) {
            $_endIndex = $this->getCuesCount() - 1;
        }
    
        //check subtitles do exist
        $startSubtitle = $this->getCue($_startIndex);
        $endSubtitle = $this->getCue($_endIndex);
        if (!$startSubtitle || !$endSubtitle) {
            return false;
        }
        
        if (!($_startTime < $_endTime)) {
            return false;
        }
        
        $shift = $_startTime - $startSubtitle->getStartMS();
        $factor = ($_endTime - $_startTime) / ($endSubtitle->getStartMS() - $startSubtitle->getStartMS());

        /* Shift subtitles to the start point */
        if ($shift) {
            $this->shift($shift, $_startIndex, $_endIndex);
        }

        /* Sync timings with proportion */
        for ($index = $_startIndex; $index <= $_endIndex; $index++) {
            $cue = $this->getCue($index);
            $cue->scale($_startTime, $factor);
        }
                
        return true;
    }

    public function build()
    {
        $this->buildPart(0, $this->getCuesCount()-1);

        return $this;
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
    public function getStats()
    {
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

        for ($i = 0; $i < $this->getCuesCount(); $i++) {
            $rs = $this->getCue($i)->getReadingSpeed();

            if ($rs < 5) {
                $this->stats['tooSlow']++;
            } elseif ($rs < 10) {
                $this->stats['slowAcceptable']++;
            } elseif ($rs < 13) {
                $this->stats['aBitSlow']++;
            } elseif ($rs < 15) {
                $this->stats['goodSlow']++;
            } elseif ($rs < 23) {
                $this->stats['perfect']++;
            } elseif ($rs < 27) {
                $this->stats['goodFast']++;
            } elseif ($rs < 31) {
                $this->stats['aBitFast']++;
            } elseif ($rs < 35) {
                $this->stats['fastAcceptable']++;
            } else {
                $this->stats['tooFast']++;
            }
        }

        return $this->stats;
    }

    public function convertTo($_output_format)
    {
        $fileFormat = explode('File', end(explode('\\', get_class($this))))[0];
        $method = strtolower($fileFormat).'2'.strtolower(rtrim($_output_format, 'File'));

        if (method_exists(new Converter(), $method)) {
            return Converter::$method($this);
        } else {
            throw new \Exception('Converter::'.$method.' is not defined.');
        }
    }
}
