<?php

namespace Captioning\Format;

use Captioning\Cue;

class TtmlCue extends Cue
{
    private $style;

    private $id;

    private $region;

    /**
     * Converts timecode format into milliseconds
     *
     * @param  string $_timecode timecode as string
     * @return int
     */
    public static function tc2ms(string $_timecode): int
    {
        return 0;
    }

    /**
     * Converts milliseconds into subrip timecode format
     *
     * @param  int $_ms
     * @return string
     */
    public static function ms2tc(int $_ms, string $_separator = ',', $isHoursPaddingEnabled = true): string
    {
        return '';
    }

    public function setStyle($_style): TtmlCue
    {
        $this->style = $_style;

        return $this;
    }

    public function getStyle()
    {
        return $this->style;
    }

    public function setId($_id): TtmlCue
    {
        $this->id = $_id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setRegion($_region): TtmlCue
    {
        $this->region = $_region;

        return $this;
    }

    public function getRegion()
    {
        return $this->region;
    }
}
