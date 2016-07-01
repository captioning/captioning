<?php

namespace Captioning\Format;

use Captioning\File;

class SubripFile extends File
{
    const PATTERN =
        '/^
                       ### First subtitle ###
        [\p{C}]{0,3}                            # BOM
        [\d]+                                   # Subtitle order.
        ((?:\r\n|\r|\n))                        # Line end.
        [\d]{2}:[\d]{2}:[\d]{2},[\d]{3}         # Start time.
        [ ]-->[ ]                               # Time delimiter.
        [\d]{2}:[\d]{2}:[\d]{2},[\d]{3}         # End time.
        (?:\1[\S ]+)+                           # Subtitle text.
                       ### Other subtitles ###
        (?:
            \1\1(?<=\r\n|\r|\n)[\d]+\1
            [\d]{2}:[\d]{2}:[\d]{2},[\d]{3}
            [ ]-->[ ]
            [\d]{2}:[\d]{2}:[\d]{2},[\d]{3}
            (?:\1[\S ]+)+
        )*
        \1*
        $/xu'
    ;

    private $defaultOptions = array('_stripTags' => false, '_stripBasic' => false, '_replacements' => false);

    private $options = array();

    public function __construct($_filename = null, $_encoding = null, $_useIconv = false)
    {
        parent::__construct($_filename, $_encoding, $_useIconv);
        $this->options = $this->defaultOptions;
    }

    public function parse()
    {
        $matches = array();
        $res = preg_match(self::PATTERN, $this->fileContent, $matches);

        if ($res === false || $res === 0) {
            throw new \Exception($this->filename.' is not a proper .srt file.');
        }

        $this->setLineEnding($matches[1]);
        $bom = pack('CCC', 0xef, 0xbb, 0xbf);
        $matches = explode($this->lineEnding.$this->lineEnding, trim($matches[0], $bom.$this->lineEnding));

        $subtitleOrder = 1;
        $subtitleTime = '';

        foreach ($matches as $match) {
            $subtitle = explode($this->lineEnding, $match, 3);
            $timeline = explode(' --> ', $subtitle[1]);

            $subtitleTimeStart = $timeline[0];
            $subtitleTimeEnd = $timeline[1];

            if (
                $subtitle[0] != $subtitleOrder++ ||
                !$this->validateTimelines($subtitleTime, $subtitleTimeStart, true) ||
                !$this->validateTimelines($subtitleTimeStart, $subtitleTimeEnd)
            ) {
                switch (true) {
                    case $subtitle[0] != $subtitleOrder - 1:
                        $errorMsg = 'Invalid subtitle order index: ' . $subtitle[0];
                        break;
                    case !$this->validateTimelines($subtitleTime, $subtitleTimeStart, true):
                        $errorMsg = 'Staring time invalid: ' . $subtitleTimeStart;
                        break;
                    case !$this->validateTimelines($subtitleTimeStart, $subtitleTimeEnd):
                        $errorMsg = 'Ending time invalid: ' . $subtitleTimeEnd;
                        break;
                }
                throw new \Exception($this->filename.' is not a proper .srt file. (' . $errorMsg . ')');
            }

            $subtitleTime = $subtitleTimeEnd;
            $cue = new SubripCue($timeline[0], $timeline[1], $subtitle[2]);
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

    /**
     * @param string $startTimeline
     * @param string $endTimeline
     * @param boolean $allowEqual
     * @return boolean
     */
    private function validateTimelines($startTimeline, $endTimeline, $allowEqual = false)
    {
        $startDateTime = \DateTime::createFromFormat('H:i:s,u', $startTimeline);
        $endDateTime = \DateTime::createFromFormat('H:i:s,u', $endTimeline);

        // If DateTime objects are equals need check milliseconds precision.
        if ($startDateTime == $endDateTime) {
            $startSeconds = $startDateTime->getTimestamp();
            $endSeconds = $endDateTime->getTimestamp();

            $startMilliseconds = ($startSeconds * 1000) + (int)substr($startTimeline, 9);
            $endMilliseconds = ($endSeconds * 1000) + (int)substr($endTimeline, 9);

            return $startMilliseconds < $endMilliseconds || ($allowEqual && $startMilliseconds === $endMilliseconds);
        }

        return $startTimeline < $endTimeline;
    }
}
