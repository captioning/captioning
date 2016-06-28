<?php

namespace Captioning\Format;

use Captioning\Cue;

class JsonCue extends Cue
{
    /**
     * @var bool
     */
    protected $startOfParagraph;

    /**
     * @var mixed
     */
    protected $duration;

    /**
     * @param string $_timecode
     * @return null
     */
    public static function tc2ms($_timecode)
    {
        return $_timecode;
    }

    /**
     * @return boolean
     */
    public function isStartOfParagraph()
    {
        return $this->startOfParagraph;
    }

    /**
     * @param $startOfParagraph
     * @return $this
     */
    public function setStartOfParagraph($startOfParagraph)
    {
        $this->startOfParagraph = $startOfParagraph;

        return $this;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param $duration
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @param mixed $_start
     * @return $this
     */
    public function setStart($_start)
    {
        parent::setStart($_start);

        if ($this->stop) {
            $this->duration = $this->stop - $this->start;
        }

        return $this;
    }

    /**
     * @param mixed $_stop
     * @return $this
     */
    public function setStop($_stop)
    {
        parent::setStop($_stop);

        if ($this->start) {
            $this->duration = $this->stop - $this->start;
        }

        return $this;
    }
}