<?php

namespace Captioning;

abstract class Cue implements CueInterface
{
    /**
     * @var mixed
     */
    protected $start;

    /**
     * @var mixed
     */
    protected $stop;

    /**
     * @var integer
     */
    protected $startMS;

    /**
     * @var integer
     */
    protected $stopMS;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var array
     */
    protected $textLines = [];

    /**
     * @var string
     */
    protected $lineEnding;

    /**
     * Cue constructor.
     * @param mixed  $_start
     * @param mixed  $_stop
     * @param string $_text
     */
    public function __construct($_start, $_stop, string $_text = '')
    {
        $this->lineEnding = File::UNIX_LINE_ENDING;

        $this->setStart($_start);
        $this->setStop($_stop);

        if (trim($_text) !== '') {
            $this->setText($_text);
        } else {
            $this->text = '';
        }
    }

    /**
     * @param mixed $_start
     * @return $this
     */
    public function setStart($_start): self
    {
        $this->start   = $_start;
        $cueClass      = get_class($this);
        $this->startMS = $cueClass::tc2ms($this->start);

        return $this;
    }

    /**
     * @param mixed $_stop
     * @return $this
     */
    public function setStop($_stop): self
    {
        $this->stop   = $_stop;
        $cueClass     = get_class($this);
        $this->stopMS = $cueClass::tc2ms($this->stop);

        return $this;
    }

    /**
     * @param int $_startMS
     * @return $this
     */
    public function setStartMS(int $_startMS): self
    {
        $this->startMS = $_startMS;
        $cueClass      = get_class($this);
        $this->start   = $cueClass::ms2tc($this->startMS);

        return $this;
    }

    /**
     * @param int $_stopMS
     * @return $this
     */
    public function setStopMS(int $_stopMS): self
    {
        $this->stopMS = $_stopMS;
        $cueClass     = get_class($this);
        $this->stop   = $cueClass::ms2tc($this->stopMS);

        return $this;
    }

    /**
     * @param string $_text
     * @return $this
     * @throws \Exception
     */
    public function setText(string $_text): self
    {
        $this->parseTextLines($_text);
        $this->getText();

        return $this;
    }

    /**
     * @param string $_lineEnding
     * @return $this|void
     */
    public function setLineEnding(string $_lineEnding): self
    {
        $lineEndings = [
            File::UNIX_LINE_ENDING,
            File::MAC_LINE_ENDING,
            File::WINDOWS_LINE_ENDING
        ];

        if (!in_array($_lineEnding, $lineEndings, true)) {
            return $this;
        }

        $this->lineEnding = $_lineEnding;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return mixed
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * @return int
     */
    public function getStartMS(): int
    {
        return $this->startMS;
    }

    /**
     * @return int
     */
    public function getStopMS(): int
    {
        return $this->stopMS;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        $this->text = implode($this->lineEnding, $this->textLines);

        return $this->text;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->stopMS - $this->startMS;
    }

    /**
     * @param string $_text
     * @throws \Exception
     */
    private function parseTextLines(string $_text)
    {
        if (trim($_text) === '') {
            throw new \Exception('No text provided.');
        }

        $this->textLines = array_map('trim', preg_split('/$\R?^/m', $_text));
    }

    /**
     * @param string $_line
     * @return $this
     */
    public function addTextLine(string $_line): self
    {
        $split = array_map('trim', preg_split('/$\R?^/m', $_line));

        if (count($split) > 1) {
            foreach ($split as $splittedLine) {
                $this->addTextLine($splittedLine);
            }
        } elseif (trim($_line) !== '') {
            $this->textLines[] = $_line;
            $this->getText();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getTextLines(): array
    {
        return $this->textLines;
    }

    /**
     * @param int $_index
     * @return string|null
     */
    public function getTextLine(int $_index)
    {
        return $this->textLines[$_index] ?? null;
    }

    /**
     * @return int
     */
    public function strlen(): int
    {
        return mb_strlen($this->getText(), 'UTF-8');
    }

    /**
     * @return float
     */
    public function getCPS(): float
    {
        return round($this->strlen() / ($this->getDuration() / 1000), 1);
    }

    /**
     * Computes Reading Speed (based on VisualSubSync algorithm)
     */
    public function getReadingSpeed(): float
    {
        $dur = $this->getDuration();
        $dur = ($dur <= 500) ? 501 : $dur;

        return ($this->strlen() * 1000) / ($dur - 500);
    }

    /**
     * Set a delay (positive or negative)
     *
     * @param int $_time Delay in milliseconds
     */
    public function shift(int $_time = 0): bool
    {
        if (!is_int($_time)) {
            return false;
        }
        if ($_time === 0) {
            return true;
        }

        $start = $this->getStartMS();
        $stop  = $this->getStopMS();

        $this->setStartMS($start + $_time);
        $this->setStopMS($stop + $_time);

        return true;
    }

    /**
     * @param     $_baseTime
     * @param int $_factor
     * @return bool
     */
    public function scale($_baseTime, int $_factor = 1): bool
    {
        if ($_factor === 1) {
            return false;
        }

        $new_start = $_baseTime + (($this->getStartMS() - $_baseTime) * $_factor);
        $new_stop  = $_baseTime + (($this->getStopMS() - $_baseTime) * $_factor);

        $this->setStartMS($new_start);
        $this->setStopMS($new_stop);

        return true;
    }

    /**
     * @param CueInterface $_cue
     * @return mixed
     */
    public static function getFormat(CueInterface $_cue)
    {
        if (!is_subclass_of($_cue, __NAMESPACE__.'\Cue')) {
            throw new \InvalidArgumentException('Invalid $_cue parameter, subclass of Cue expected.');
        }

        $fullNamespace = explode('\\', get_class($_cue));
        $tmp           = explode('Cue', end($fullNamespace));

        return $tmp[0];
    }

    /**
     * @param int $_ms
     * @param string $_separator
     * @return string
     */
    public static function ms2tc(int $_ms, string $_separator = '.', bool $isHoursPaddingEnabled = true): string
    {
        $tc_ms = round((($_ms / 1000) - floor($_ms / 1000)) * 1000);
        $x = $_ms / 1000;
        $tc_s = floor((int)$x % 60);
        $x /= 60;
        $tc_m = floor((int)$x % 60);
        $x /= 60;
        $tc_h = floor((int)$x % 24);

        if ($isHoursPaddingEnabled) {
            $timecode = str_pad($tc_h, 2, '0', STR_PAD_LEFT).':';
        } else {
            $timecode = $tc_h.':';
        }
        $timecode .= str_pad($tc_m, 2, '0', STR_PAD_LEFT).':'
            .str_pad($tc_s, 2, '0', STR_PAD_LEFT).$_separator
            .static::getLastTimeCodePart($tc_ms);

        return $timecode;
    }

    /**
     * @param int $tc_ms
     * @return string
     */
    protected static function getLastTimeCodePart(int $tc_ms): string
    {
        return str_pad($tc_ms, 3, '0', STR_PAD_LEFT);
    }
}
