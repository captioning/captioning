<?php

namespace Captioning\Format;

use Captioning\File;

class SubripFile extends File
{

    const PATTERN_HEADER =
    '/^
    ([\d]+)                                          # Subtitle order.
    [ ]*                                             # Possible whitespace
    ((?:\r\n|\r|\n))                                 # Line ending
    /xu'
    ;

    const PATTERN_ORDER =
    '/^
    \d+
    [ ]*
    $/x';

    const PATTERN_TIMESTAMP =
    '/^
    ([\d]{1,2}:[\d]{1,2}:[\d]{1,2} # HH:MM:SS
      (?:,[\d]{1,3})?)             # Optional milliseconds
    $/xu'
    ;

    private $defaultOptions = array('_stripTags' => false, '_stripBasic' => false, '_replacements' => false, '_requireStrictFileFormat' => true);

    private $options = array();

    public function __construct($_filename = null, $_encoding = null, $_useIconv = false, $_requireStrictFileFormat = true)
    {
        $this->options = $this->defaultOptions;
        $this->options['_requireStrictFileFormat'] = $_requireStrictFileFormat;

        parent::__construct($_filename, $_encoding, $_useIconv);
    }

    public function parse()
    {
        $content = $this->fileContent;

        // Strip UTF-8 BOM
        $bom = pack('CCC', 0xef, 0xbb, 0xbf);
        if (substr($content, 0, 3) === $bom) {
          $content = substr($content, 3);
        }

        $matches = array();
        $res = preg_match(self::PATTERN_HEADER, $content, $matches);
        if ($res === false || $res === 0) {
            throw new \Exception($this->filename.' is not a proper .srt file (Invalid header).');
        }

        $this->setLineEnding($matches[2]);

        $subtitleTimeLast = false;
        $subtitleOrder = 1;
        $strict = $this->options['_requireStrictFileFormat'];
        if (!$strict) {
          if ($matches[1] === '0') {
            // Allow starting index at 0
            $subtitleOrder = 0;
          }
        }

        $lines = explode($this->lineEnding, $content);

        $state = 'order';
        foreach ($lines as $lineNumber => $line) {
        
            switch ($state) {
            case 'order':
                if (!preg_match(self::PATTERN_ORDER, $line)) {
                    throw new \Exception($this->filename.' is not a proper .srt file. (Expected subtitle order index at line '.$lineNumber.')');
                }
                $subtitleIndex = intval($line);
                if ($strict && $subtitleOrder !== $subtitleIndex) {
                    throw new \Exception($this->filename.' is not a proper .srt file. (Invalid subtitle order index: '.$line.' at line '.$lineNumber.')');
                }
                $state = 'time';
                break;

            case 'time':
                $timeline = explode(' --> ', $line);
                if (count($timeline) !== 2) {
                  throw new \Exception($this->filename." is not a proper .srt file. (Invalid timestamp delimiter at line ".$lineNumber.")");
                }

                $subtitleTimeStart = trim($timeline[0]);
                $subtitleTimeEnd = trim($timeline[1]);
                if (!preg_match(self::PATTERN_TIMESTAMP, $subtitleTimeStart)) {
                  throw new \Exception($this->filename.' is not a proper .srt file. (Invalid start timestamp format '.$subtitleTimeStart.' at line '.$lineNumber.')');
                }
                if (!preg_match(self::PATTERN_TIMESTAMP, $subtitleTimeEnd)) {
                  throw new \Exception($this->filename.' is not a proper .srt file. (Invalid end timestamp format '.$subtitleTimeEnd.' at line '.$lineNumber.')');
                }

                $subtitleTimeStart = $this->cleanUpTimecode($subtitleTimeStart);
                $subtitleTimeEnd = $this->cleanUpTimecode($subtitleTimeEnd);

                if ($subtitleTimeLast &&
                    $strict && // Allow overlapping timecodes when not in "strict mode"
                    !$this->validateTimelines($subtitleTimeLast, $subtitleTimeStart, true)
                ) {
                    throw new \Exception($this->filename.' is not a proper .srt file. (Starting time invalid: '.$subtitleTimeStart.' at line '.$lineNumber.')');
                }
                if (!$this->validateTimelines($subtitleTimeStart, $subtitleTimeEnd, !$strict)) {
                    throw new \Exception($this->filename.' is not a proper .srt file. (Ending time invalid: '.$subtitleTimeEnd.' at line '.$lineNumber.')');
                }
                $subtitleText = array();
                $state = 'text';
                break;

            case 'text':
                $subtitleText[] = $line;
                if ($lineNumber === count($lines) - 1 || ($line === '' && $lines[$lineNumber+1] !== '')) {
                  $state = 'end';
                  // Fall through...
                } else {
                  break;
                }
                // Fall through...

            case 'end':
                $subtitleTimeLast = $subtitleTimeEnd;
                $subtitleOrder++;

                $cue = new SubripCue($subtitleTimeStart, $subtitleTimeEnd, implode($this->lineEnding, $subtitleText));
                $cue->setLineEnding($this->lineEnding);
                $this->addCue($cue);

                $state = 'order';
                break;
            }
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

    /**
     * Add milliseconds and leading zeroes if they are missing
     *
     * @param $timecode
     *
     * @return mixed
     */
    private function cleanUpTimecode($timecode)
    {
        strpos($timecode, ',') ?: $timecode .= ',000';

        $patternNoLeadingZeroes = '/(?:(?<=\:)|^)\d(?=(:|,))/';

        return preg_replace_callback($patternNoLeadingZeroes, function($matches)
        {
            return sprintf('%02d', $matches[0]);
        }, $timecode);
    }
}
