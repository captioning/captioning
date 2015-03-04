<?php

namespace Captioning;

use Captioning\CueInterface;

abstract class Cue implements CueInterface
{
    protected $start;
    protected $stop;
    protected $startMS;
    protected $stopMS;
    protected $text;
    protected $textLines = array();
    protected $lineEnding;

    public function __construct($_start, $_stop, $_text = '')
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

    public function setStart($_start)
    {
        $this->start   = $_start;
        $cueClass      = get_class($this);
        $this->startMS = $cueClass::tc2ms($this->start);

        return $this;
    }

    public function setStop($_stop)
    {
        $this->stop   = $_stop;
        $cueClass     = get_class($this);
        $this->stopMS = $cueClass::tc2ms($this->stop);

        return $this;
    }

    public function setStartMS($_startMS)
    {
        $this->startMS = $_startMS;
        $cueClass      = get_class($this);
        $this->start   = $cueClass::ms2tc($this->startMS);

        return $this;
    }

    public function setStopMS($_stopMS)
    {
        $this->stopMS = $_stopMS;
        $cueClass     = get_class($this);
        $this->stop   = $cueClass::ms2tc($this->stopMS);

        return $this;
    }

    public function setText($_text)
    {
        $this->parseTextLines($_text);
        $this->getText();

        return $this;
    }

    public function setLineEnding($_lineEnding)
    {
        $lineEndings = array(
            File::UNIX_LINE_ENDING,
            File::MAC_LINE_ENDING,
            File::WINDOWS_LINE_ENDING
        );

        if (!in_array($_lineEnding, $lineEndings)) {
            return;
        }

        $this->lineEnding = $_lineEnding;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getStop()
    {
        return $this->stop;
    }

    public function getStartMS()
    {
        return $this->startMS;
    }

    public function getStopMS()
    {
        return $this->stopMS;
    }

    public function getText()
    {
        $this->text = implode($this->lineEnding, $this->textLines);

        return $this->text;
    }

    public function getDuration()
    {
        return $this->stopMS - $this->startMS;
    }

    private function parseTextLines($_text)
    {
        if (trim($_text) === '') {
            throw new \Exception('No text provided.');
        }

        $this->textLines = array_map('trim', preg_split('/$\R?^/m', $_text));
    }

    public function addTextLine($_line)
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

    public function getTextLines()
    {
        return $this->textLines;
    }

    public function getTextLine($_index)
    {
        return isset($this->textLines[$_index]) ? $this->textLines[$_index] : null;
    }

    public function strlen()
    {
        return mb_strlen($this->getText(), 'UTF-8');
    }

    public function getCPS()
    {
        return round($this->strlen() / ($this->getDuration() / 1000), 1);
    }

    /**
     * Computes Reading Speed (based on VisualSubSync algorithm)
     */
    public function getReadingSpeed()
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
    public function shift($_time = 0)
    {
        if (!is_int($_time)) {
            return false;
        }
        if ($_time == 0) {
            return true;
        }

        $start = $this->getStartMS();
        $stop  = $this->getStopMS();

        $this->setStartMS($start + $_time);
        $this->setStopMS($stop + $_time);

        return true;
    }

    public function scale($_baseTime, $_factor = 1)
    {
        if ($_factor == 1) {
            return false;
        }

        $new_start = $_baseTime + (($this->getStartMS() - $_baseTime) * $_factor);
        $new_stop  = $_baseTime + (($this->getStopMS() - $_baseTime) * $_factor);

        $this->setStartMS($new_start);
        $this->setStopMS($new_stop);

        return true;
    }

    public static function getFormat($_cue)
    {
        if (!is_subclass_of($_cue, __NAMESPACE__.'\Cue')) {
            throw new \InvalidArgumentException('Invalid $_cue parameter, subclass of Cue expected.');
        }

        $fullNamespace = explode('\\', get_class($_cue));
        $tmp           = explode('Cue', end($fullNamespace));

        return $tmp[0];
    }
}
