<?php

namespace Captioning\Format;

use Captioning\File;

class SubripFile extends File
{
    const PATTERN = '#[0-9]+(?:\r\n|\r|\n)([0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3}) --> ([0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3})(?:\r\n|\r|\n)((?:.*(?:\r\n|\r|\n))*?)(?:\r\n|\r|\n)#';

    protected $lineEnding = File::WINDOWS_LINE_ENDING;

    private $defaultOptions = array ('_stripTags' => false, '_stripBasic' => false, '_replacements' => false);

    private $options = array();

    public function __construct($_filename = null, $_encoding = null, $_useIconv = false)
    {
        $this->options = $this->defaultOptions;
        parent::__construct($_filename, $_encoding, $_useIconv);
    }

    public function parse()
    {
        $matches = array();
        $res = preg_match_all(self::PATTERN, $this->fileContent, $matches);

        if (!$res || $res == 0) {
            throw new \Exception($this->filename.' is not a proper .srt file.');
        }

        $entries_count = count($matches[1]);

        for ($i = 0; $i < $entries_count; $i++) {
            $cue = new SubripCue($matches[1][$i], $matches[2][$i], $matches[3][$i]);
            $cue->setLineEnding($this->lineEnding);
            $this->addCue($cue);
        }

        return $this;
    }

    public function build()
    {
        $this->buildPart(0, $this->getCuesCount() - 1);

        return $this;
    }

    public function buildPart($_from, $_to)
    {
        $this->sortCues();
        
        $i = 1;
        $buffer = "";
        if ($_from < 0 || $_from >= $this->getCuesCount()) {
            $_from = 0;
        }

        if ($_to < 0 || $_to >= $this->getCuesCount()) {
            $_to = $this->getCuesCount() - 1;
        }

        for ($j = $_from; $j <= $_to; $j++) {
            $cue = $this->getCue($j);
            $buffer .= $i.$this->lineEnding;
            $buffer .= $cue->getTimeCodeString().$this->lineEnding;
            $buffer .= $cue->getText(
                    $this->options['_stripTags'],
                    $this->options['_stripBasic'],
                    $this->options['_replacements']
                );
            $buffer .= $this->lineEnding;
            $buffer .= $this->lineEnding;
            $i++;
        }
        
        $this->fileContent = $buffer;

        return $this;
    }

    /**
     * @param array $options array('_stripTags' => false, '_stripBasic' => false, '_replacements' => false)
     * @return SubripFile
     */
    public function setOptions(array $options)
    {
        if($this->validateOptions($options)) {
            $this->options = array_merge($this->defaultOptions, $options);
        } else {
            throw new \UnexpectedValueException('Options consists not allowed keys');
        }
        return $this;
    }

    /**
     * @return SubripFile
     */
    public function resetOptions()
    {
        $this->options = $this->defaultOptions;
        return $this;
    }

    /**
     * @param array $options
     * @return bool
     */
    private function validateOptions(array $options)
    {
        foreach(array_keys($options) as $key) {
            if (!array_key_exists($key, $this->defaultOptions)) {
                return false;
            }
        }
        return true;
    }
}
