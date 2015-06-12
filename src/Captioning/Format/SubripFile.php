<?php

namespace Captioning\Format;

use Captioning\File;

class SubripFile extends File
{
    const PATTERN =
        '/^
        ([\d]+)                                  # Subtitle order.
        (?:\r\n|\r|\n)                           # Line end.
        ([\d]{2}:[\d]{2}:[\d]{2},[\d]{3})        # Start time.
        \s-->\s                                  # Time delimiter.
        ([\d]{2}:[\d]{2}:[\d]{2},[\d]{3})        # End time.
        (?:\r\n|\r|\n)                           # Line end.
        ((?:.|\n)*?)                             # Subtitle text.
        (?:\r\n|\r|\n)                           # End blank line.
        $/mx'
    ;

    protected $lineEnding = File::WINDOWS_LINE_ENDING;

    private $defaultOptions = array('_stripTags' => false, '_stripBasic' => false, '_replacements' => false);

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

        for ($i = 0, $subtitleOrder = 1; $i < $entries_count; $i++, $subtitleOrder++) {
            if ($matches[1][$i] != $subtitleOrder) {
                throw new \Exception($this->filename.' is not a proper .srt file.');
            }
            $cue = new SubripCue($matches[2][$i], $matches[3][$i], $matches[4][$i]);
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
     * @throws \UnexpectedValueException
     */
    public function setOptions(array $options)
    {
        if (!$this->validateOptions($options)) {
            throw new \UnexpectedValueException('Options consists not allowed keys');
        }
        $this->options = array_merge($this->defaultOptions, $options);
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
        foreach (array_keys($options) as $key) {
            if (!array_key_exists($key, $this->defaultOptions)) {
                return false;
            }
        }
        return true;
    }
}
