<?php

namespace Captioning\Format;

use Captioning\File;

class SubstationalphaFile extends File {

    const PATTERN_V4 = '#Dialogue: Marked=([0-9]),([0-9]:[0-9]{2}:[0-9]{2}.[0-9]{2}),([0-9]:[0-9]{2}:[0-9]{2}.[0-9]{2}),(.*),(.*),([0-9]{4}),([0-9]{4}),([0-9]{4}),([^,]*),(.+)#';
    const PATTERN_V4_PLUS = '#Dialogue: ([0-9]),([0-9]:[0-9]{2}:[0-9]{2}.[0-9]{2}),([0-9]:[0-9]{2}:[0-9]{2}.[0-9]{2}),(.*),(.*),([0-9]{1,4}),([0-9]{1,4}),([0-9]{1,4}),([^,]*),(.+)#';
    const SCRIPT_TYPE_V4 = 'v4.00';
    const SCRIPT_TYPE_V4_PLUS = 'v4.00+';
    const STYLES_V4 = 'V4';
    const STYLES_V4_PLUS = 'V4+';

    protected $headers;
    protected $stylesVersion;
    protected $styles;
    protected $excludedStyles;
    protected $events;
    protected $comments;

    public function __construct($_filename = null, $_encoding = null, $_useIconv = false) {
        $this->headers = array(
            'Title' => '<untitled>',
            'Original Script' => '<unknown>',
            'Original Translation' => null,
            'Original Editing' => null,
            'Original Timing' => null,
            'Synch Point' => null,
            'Script Updated By' => null,
            'Update Details' => null,
            'ScriptType' => null,
            'Collisions' => 'Normal',
            'PlayResX' => 384,
            'PlayResY' => 288,
            'PlayDepth' => 0,
            'Timer' => '100.0',
            'WrapStyle' => 0
        );

        $this->stylesVersion = self::STYLES_V4_PLUS;

        $this->styles = array(
            'Name' => 'Default',
            'Fontname' => 'Arial',
            'Fontsize' => 20,
            'PrimaryColour' => '&H00FFFFFF',
            'SecondaryColour' => '&H00000000',
            'TertiaryColour' => '&0000000',
            'OutlineColour' => '&H00000000',
            'BackColour' => '&H00000000',
            'Bold' => 0,
            'Italic' => 0,
            'Underline' => 0,
            'StrikeOut' => 0,
            'ScaleX' => 100,
            'ScaleY' => 100,
            'Spacing' => 0,
            'Angle' => 0,
            'BorderStyle' => 1,
            'Outline' => 2,
            'Shadow' => 0,
            'Alignment' => 2,
            'MarginL' => 15,
            'MarginR' => 15,
            'MarginV' => 15,
            'AlphaLevel' => 0,
            'Encoding' => 0
        );

        $this->excludedStyles = array(
            self::STYLES_V4 => array('OutlineColour', 'Underline', 'StrikeOut', 'ScaleX', 'ScaleY', 'Spacing', 'Angle'),
            self::STYLES_V4_PLUS => array('TertiaryColour', 'AlphaLevel')
        );

        $this->events = array(
            self::SCRIPT_TYPE_V4 => array('Marked', 'Start', 'End', 'Style', 'Name', 'MarginL', 'MarginR', 'MarginV', 'Effect', 'Text'),
            self::SCRIPT_TYPE_V4_PLUS => array('Layer', 'Start', 'End', 'Style', 'Name', 'MarginL', 'MarginR', 'MarginV', 'Effect', 'Text')
        );

        $this->comments = array();

        parent::__construct($_filename, $_encoding, $_useIconv);
    }

    /**
     * Set script type.
     * 
     * @param string $_value Script type, eg. 'v4.00+'
     */
    public function setScriptType($_value) {
        if (!in_array($_value, array(self::SCRIPT_TYPE_V4, self::SCRIPT_TYPE_V4_PLUS))) {
            throw new \InvalidArgumentException('Invalid script type');
        }

        return $this->setHeader('ScriptType', $_value);
    }

    /**
     * Return script type, eg. 'v4.00'
     * @return string
     */
    public function getScriptType() {
        return $this->getHeader('ScriptType');
    }

    public function setHeader($_name, $_value) {
        if (array_key_exists($_name, $this->headers)) {
            $this->headers[$_name] = $_value;
        }

        return $this;
    }

    public function getHeader($_name) {
        return isset($this->headers[$_name]) ? $this->headers[$_name] : false;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function setStylesVersion($stylesVersion) {
        if (!in_array($stylesVersion, array(self::STYLES_V4, self::STYLES_V4_PLUS))) {
            throw new \InvalidArgumentException('Invalid styles version');
        }

        $this->stylesVersion = $stylesVersion;

        return $this;
    }

    public function getStylesVersion() {
        return $this->stylesVersion;
    }

    public function setStyle($_name, $_value) {
        if (isset($this->styles[$_name])) {
            $this->styles[$_name] = $_value;
        }
    }

    public function getStyle($_name) {
        return isset($this->styles[$_name]) ? $this->styles[$_name] : false;
    }

    public function getStyles() {
        return $this->styles;
    }

    public function getNeededStyles() {
        $styles = $this->styles;

        foreach ($this->excludedStyles[$this->stylesVersion] as $styleName) {
            unset($styles[$styleName]);
        }

        return $styles;
    }

    public function setStyles($_styles) {
        $this->styles = $_styles;
    }

    public function setEvents($_events) {
        if (!empty($_events) && is_array($_events)) {
            $this->events = $_events;
        }
    }

    public function getEvents() {
        return $this->events;
    }

    public function addComment($_comment) {
        $this->comments[] = $_comment;
    }

    public function getComments() {
        return $this->comments;
    }

    public function getNeededEvents() {
        return $this->events[$this->getScriptType()];
    }

    public function parse() {
        $fileContentArray = $this->getFileContentAsArray();
       
        while (($line = $this->getNextValueFromArray($fileContentArray)) !== false) {
            $line=preg_replace('/[\x{feff}-\x{ffff}]/u', '', $line);
            
            // parsing headers
            if ($line === '[Script Info]') {
                while (($line = trim($this->getNextValueFromArray($fileContentArray))) !== '') {
                    if ($line[0] == ';') {
                        $this->addComment(ltrim($line, '; '));
                    } else {
                        $tmp = explode(':', $line);
                        if (count($tmp) == 2) {
                            $this->setHeader(trim($tmp[0]), trim($tmp[1]));
                        }
                    }
                }
            }
            
            // parsing styles
            if ($line === '[V4+ Styles]') {
                $line = $this->getNextValueFromArray($fileContentArray);
                $tmp_styles = array();
                $tmp = explode(':', $line);
                if ($tmp[0] !== 'Format') {
                    throw new \Exception($this->filename . ' is not valid file.');
                }
                $tmp2 = explode(',', $tmp[1]);

                foreach ($tmp2 as $s) {
                    $tmp_styles[trim($s)] = null;
                }

                $line = $this->getNextValueFromArray($fileContentArray);
                $tmp = explode(':', $line);
                if ($tmp[0] !== 'Style') {
                    throw new \Exception($this->filename . ' is not valid file.');
                }
                $tmp2 = explode(',', $tmp[1]);
                $i = 0;
                foreach (array_keys($tmp_styles) as $s) {
                    $this->setStyle($s, trim($tmp2[$i]));
                    $i++;
                }

                break;
            }
        }

        if ($this->getScriptType() === false) {
            throw new \Exception($this->filename . ' is not a proper .ass file (empty ScriptType).');
        }
        
        $matches = array();
        $pattern = $this->getPattern();
        preg_match_all($pattern, $this->fileContent, $matches);
        $matchesCount = count($matches[1]);
        if ($matchesCount===0) {
                throw new \Exception($this->filename . ' is not a proper .ass file (no events).');
        }
        
        for ($i = 0; $i < $matchesCount; $i++) {
            $cue = new SubstationalphaCue(
                    $matches[2][$i], $matches[3][$i], $matches[10][$i], $matches[1][$i], $matches[4][$i], $matches[5][$i], $matches[6][$i], $matches[7][$i], $matches[8][$i], $matches[9][$i]
            );

            $this->addCue($cue);
        }
        
        return $this;
    }

    public function buildPart($_from, $_to) {
        if ($this->getHeader('ScriptType') === false) {
            throw new \Exception('Script type not set');
        }

        // headers
        $buffer = '[Script Info]' . $this->lineEnding;
        foreach ($this->comments as $comment) {
            $buffer .= '; ' . str_replace($this->lineEnding, $this->lineEnding . "; ", $comment) . $this->lineEnding;
        }
        foreach ($this->headers as $key => $value) {
            if ($value !== null) {
                $buffer .= $key . ': ' . $value . $this->lineEnding;
            }
        }
        $buffer .= $this->lineEnding;

        // styles
        $buffer .= '[' . $this->stylesVersion . ' Styles]' . $this->lineEnding;

        $styles = $this->getNeededStyles();
        $buffer .= 'Format: ' . implode(', ', array_keys($styles)) . $this->lineEnding;
        $buffer .= 'Style: ' . implode(', ', array_values($styles)) . $this->lineEnding;

        // events (= cues)
        $buffer .= $this->lineEnding;
        $events = $this->getNeededEvents();
        $buffer .= '[Events]' . $this->lineEnding;
        $buffer .= 'Format: ' . implode(', ', $events) . $this->lineEnding;

        $scriptType = $this->getScriptType();
        foreach ($this->cues as $cue) {
            $buffer .= $cue->toString($scriptType) . $this->lineEnding;
        }

        $this->fileContent = $buffer;
        return $this;
    }

    /**
     * Return event pattern.
     * 
     * @return string
     */
    private function getPattern() {
        if ($this->getScriptType() == self::SCRIPT_TYPE_V4) {
            return self::PATTERN_V4;
        }

        return self::PATTERN_V4_PLUS;
    }

}
