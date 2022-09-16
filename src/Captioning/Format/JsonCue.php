<?php

namespace Captioning\Format;

use Captioning\Cue;
use Captioning\CueInterface;

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
    public static function tc2ms(string $_timecode): int
    {
        return $_timecode;
    }

    /**
     * @return boolean
     */
    public function isStartOfParagraph(): bool
    {
        return $this->startOfParagraph;
    }

    /**
     * @param $startOfParagraph
     * @return $this
     */
    public function setStartOfParagraph($startOfParagraph): JsonCue
    {
        $this->startOfParagraph = $startOfParagraph;

        return $this;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param $duration
     * @return $this
     */
    public function setDuration($duration): JsonCue
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @param mixed $_start
     * @return $this
     */
    public function setStart($_start): Cue
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
    public function setStop($_stop): Cue
    {
        parent::setStop($_stop);

        if ($this->start) {
            $this->duration = $this->stop - $this->start;
        }

        return $this;
    }
}