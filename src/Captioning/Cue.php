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
    protected $duration;
    protected $CPS;
    protected $readingSpeed;

    public function __construct($_start, $_stop, $_text)
    {
        $this->setStart($_start);
        $this->setStop($_stop);
        $this->setText($_text);
    }

    public function setStart($_start)
    {
        $this->start = $_start;
        $cueClass = get_class($this);
        $this->startMS = $cueClass::tc2ms($this->start);
    }

    public function setStop($_stop)
    {
        $this->stop = $_stop;
        $cueClass = get_class($this);
        $this->stopMS = $cueClass::tc2ms($this->stop);
    }

    public function setStartMS($_startMS)
    {
        $this->startMS = $_startMS;
        $cueClass = get_class($this);
        $this->start = $cueClass::ms2tc($this->startMS);
    }

    public function setStopMS($_stop)
    {
        $this->stop = $_stop;
        $cueClass = get_class($this);
        $this->stopMS = $cueClass::ms2tc($this->stop);
    }

    public function setText($_text)
    {
        $this->text = trim($_text);
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
        return $this->text;
    }

    public function getDuration()
    {
        return $this->stopMS - $this->startMS;
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
        $dur = ($dur <= 500) ? $dur : 501;

        return ($this->strlen() * 1000) / ($dur - 500);
    }
}
