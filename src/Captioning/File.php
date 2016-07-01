<?php

namespace Captioning;

abstract class File implements FileInterface
{
    const DEFAULT_ENCODING = 'UTF-8';

    const UNIX_LINE_ENDING    = "\n";
    const MAC_LINE_ENDING     = "\r";
    const WINDOWS_LINE_ENDING = "\r\n";

    /**
     * @var array
     */
    protected $cues;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $encoding = self::DEFAULT_ENCODING;

    /**
     * @var bool
     */
    protected $useIconv;

    /**
     * @var string
     */
    protected $lineEnding;

    /**
     * @var string
     */
    protected $fileContent;

    /**
     * @var array
     */
    protected $stats;

    /**
     * File constructor.
     * @param null       $_filename
     * @param null       $_encoding
     * @param bool|false $_useIconv
     */
    public function __construct($_filename = null, $_encoding = null, $_useIconv = false)
    {
        $this->lineEnding = self::UNIX_LINE_ENDING;

        if ($_filename !== null) {
            $this->setFilename($_filename);
        }

        if ($_encoding !== null) {
            $this->setEncoding($_encoding);
        }

        $this->useIconv = $_useIconv;

        if ($this->getFilename() !== null) {
            $this->loadFromFile();
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

    /**
     * @param string $_filename The filename
     * @return $this
     */
    public function setFilename($_filename)
    {
        $this->filename = file_exists($_filename) ? $_filename : null;

        return $this;
    }

    /**
     * @param string $_encoding
     * @return $this
     */
    public function setEncoding($_encoding)
    {
        $this->encoding = $_encoding;

        return $this;
    }

    /**
     * @param bool $_useIconv
     * @return $this
     */
    public function setUseIconv($_useIconv)
    {
        $this->useIconv = $_useIconv;

        return $this;
    }

    /**
     * @param string $_lineEnding
     */
    public function setLineEnding($_lineEnding)
    {
        $lineEndings = array(
            self::UNIX_LINE_ENDING,
            self::MAC_LINE_ENDING,
            self::WINDOWS_LINE_ENDING
        );
        if (!in_array($_lineEnding, $lineEndings)) {
            return;
        }

        $this->lineEnding = $_lineEnding;

        if ($this->getCuesCount() > 0) {
            foreach ($this->cues as $cue) {
                $cue->setLineEnding($this->lineEnding);
            }
        }
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getFileContent()
    {
        return $this->fileContent;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @return bool|false
     */
    public function getUseIconv()
    {
        return $this->useIconv;
    }

    /**
     * @param integer $_index
     * @return Cue|null
     */
    public function getCue($_index)
    {
        return isset($this->cues[$_index]) ? $this->cues[$_index] : null;
    }

    /**
     * @return Cue|null
     */
    public function getFirstCue()
    {
        return isset($this->cues[0]) ? $this->cues[0] : null;
    }

    /**
     * @return Cue|null
     */
    public function getLastCue()
    {
        $count = count($this->cues);

        return ($count > 0) ? $this->cues[$count - 1] : null;
    }

    /**
     * @return array
     */
    public function getCues()
    {
        return $this->cues;
    }

    /**
     * @return integer
     */
    public function getCuesCount()
    {
        return count($this->cues);
    }

    /**
     * @param null $_filename
     * @return $this
     * @throws \Exception
     */
    public  function loadFromFile($_filename = null)
    {
        if ($_filename === null) {
            $_filename = $this->filename;
        } else {
            $this->filename = $_filename;
        }

        if (!file_exists($_filename)) {
            throw new \Exception('File "'.$_filename.'" not found.');
        }

        if (!($content = file_get_contents($this->filename))) {
            throw new \Exception('Could not read file content ('.$_filename.').');
        }

        $this->loadFromString($content);

        return $this;
    }

    /**
     * @param string $_str
     * @return $this
     */
    public function loadFromString($_str)
    {
        // Clear cues from previous runs
        $this->cues = array();
        $this->fileContent = $_str;

        $this->encode();
        $this->parse();

        return $this;
    }

    /**
     * Searches a word/expression and returns ids of the matched entries
     *
     * @param string $_word
     * @param boolean $_case_sensitive
     * @param boolean $_strict
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
        $cueClass = self::getExpectedCueClass($this);
        $start = is_int($_start) ? $_start : $cueClass::tc2ms($_start);

        $prev_stop = 0;
        $i = 0;
        foreach ($this->cues as $cue) {
            if (($start > $prev_stop && $start < $cue->getStart()) || ($start >= $cue->getStart() && $start < $cue->getStop())) {
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
     * @return File
     */
    public function addCue($_mixed, $_start = null, $_stop = null)
    {
        $fileFormat = self::getFormat($this);

        // if $_mixed is a Cue
        if (is_subclass_of($_mixed, __NAMESPACE__.'\Cue')) {
            $cueFormat = Cue::getFormat($_mixed);
            if ($cueFormat !== $fileFormat) {
                throw new \Exception("Can't add a $cueFormat cue in a $fileFormat file.");
            }
            $_mixed->setLineEnding($this->lineEnding);
            $this->cues[] = $_mixed;
        } else {
            $cueClass = self::getExpectedCueClass($this);
            $cue = new $cueClass($_start, $_stop, $_mixed);
            $cue->setLineEnding($this->lineEnding);
            $this->cues[] = $cue;
        }

        return $this;
    }

    /**
     * Removes a cue
     *
     * @param int $_index
     * @return File
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
     * @return File
     */
    public function changeFPS($_old_fps, $_new_fps)
    {
        $cuesCount = $this->getCuesCount();
        for ($i = 0; $i < $cuesCount; $i++) {
            $cue = $this->getCue($i);

            $old_start = $cue->getStart();
            $old_stop  = $cue->getStop();

            $new_start = $old_start * ($_new_fps / $_old_fps);
            $new_stop  = $old_stop * ($_new_fps / $_old_fps);

            $cue->setStart($new_start);
            $cue->setStop($new_stop);
        }

        return $this;
    }

    /**
     * @param FileInterface $_file
     * @return $this
     * @throws \Exception
     */
    public function merge(FileInterface $_file)
    {
        if (!is_a($_file, get_class($this))) {
            throw new \Exception('Can\'t merge! Wrong format: '.$this->getFormat($_file));
        }

        $this->cues = array_merge($this->cues, $_file->getCues());
        $this->sortCues();

        return $this;
    }

    /**
     * Shifts a range of subtitles a specified amount of time.
     *
     * @param int $_time The time to use (ms), which can be positive or negative.
     * @param int $_startIndex The subtitle index the range begins with.
     * @param int $_endIndex The subtitle index the range ends with.
     */
    public function shift($_time, $_startIndex = null, $_endIndex = null)
    {
        if (!is_int($_time)) {
            return false;
        }
        if ($_time == 0) {
            return true;
        }

        if (null === $_startIndex) {
            $_startIndex = 0;
        }
        if (null === $_endIndex) {
            $_endIndex = $this->getCuesCount() - 1;
        }

        $startCue = $this->getCue($_startIndex);
        $endCue   = $this->getCue($_endIndex);

        //check subtitles do exist
        if (!$startCue || !$endCue) {
            return false;
        }

        for ($i = $_startIndex; $i <= $_endIndex; $i++) {
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
        if (!$_syncLast) {
            $_endIndex--;
        }

        //check subtitles do exist
        $startSubtitle = $this->getCue($_startIndex);
        $endSubtitle   = $this->getCue($_endIndex);
        if (!$startSubtitle || !$endSubtitle || ($_startTime >= $_endTime)) {
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

    /**
     * @return $this
     */
    public function build()
    {
        $this->buildPart(0, $this->getCuesCount() - 1);

        return $this;
    }

    /**
     * Saves the file
     *
     * @param string $filename
     * @param bool $writeBOM
     */
    public function save($filename = null, $writeBOM = false)
    {
        if ($filename === null) {
            $filename = $this->filename;
        }

        if (trim($this->fileContent) == '') {
            $this->build();
        }

        $file_content = $this->fileContent;
        if (strtolower($this->encoding) != 'utf-8') {
            if ($this->useIconv) {
                $file_content = iconv('UTF-8', $this->encoding, $file_content);
            } else {
                $file_content = mb_convert_encoding($file_content, $this->encoding, 'UTF-8');
            }
        }

        if ($writeBOM) {
            $file_content = "\xef\xbb\xbf".$file_content;
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

        $cuesCount = $this->getCuesCount();
        for ($i = 0; $i < $cuesCount; $i++) {
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

    /**
     * @param $_file
     * @return mixed
     */
    public static function getFormat(FileInterface $_file)
    {
        if (!is_subclass_of($_file, __NAMESPACE__.'\File')) {
            throw new \InvalidArgumentException('Expected subclass of File');
        }

        $fullNamespace = explode('\\', get_class($_file));
        $tmp           = explode('File', end($fullNamespace));

        return $tmp[0];
    }

    /**
     * @param FileInterface $_file
     * @param bool|true     $_full_namespace
     * @return string
     */
    public static function getExpectedCueClass(FileInterface $_file, $_full_namespace = true)
    {
        $format = self::getFormat($_file).'Cue';

        if ($_full_namespace) {
            $tmp    = explode('\\', get_class($_file));
            array_pop($tmp);
            $format = implode('\\', $tmp).'\\'.$format;
        }

        return $format;
    }

    /**
     * @param string $_output_format
     * @return mixed
     */
    public function convertTo($_output_format)
    {
        $fileFormat = self::getFormat($this);
        $method     = strtolower($fileFormat).'2'.strtolower(rtrim($_output_format, 'File'));

        if (method_exists(new Converter(), $method)) {
            return Converter::$method($this);
        }
        return Converter::defaultConverter($this, $_output_format);
    }

    /**
     * Encode file content
     */
    protected function encode()
    {
        if ($this->useIconv) {
            $this->fileContent = iconv($this->encoding, 'UTF-8', $this->fileContent);
        } else {
            $this->fileContent = mb_convert_encoding($this->fileContent, 'UTF-8', $this->encoding);
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getFileContentAsArray()
    {
        if (empty($this->fileContent)) {
            $this->loadFromFile($this->filename);
        }
        $fileContent = str_replace( // So we change line endings to one format
            array(
                self::WINDOWS_LINE_ENDING,
                self::MAC_LINE_ENDING,
            ),
            self::UNIX_LINE_ENDING,
            $this->fileContent
        );
        $fileContentArray = explode(self::UNIX_LINE_ENDING, $fileContent); // Create array from file content

        return $fileContentArray;
    }

    /**
     * @param array $array
     * @return mixed
     */
    protected function getNextValueFromArray(array &$array)
    {
        $element = each($array);
        if (is_array($element)) {
            return $element['value'];
        }
        return false;
    }
}
