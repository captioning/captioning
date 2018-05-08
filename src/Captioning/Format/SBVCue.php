<?php

namespace Captioning\Format;

use Captioning\Cue;

class SBVCue extends Cue
{
    public static function tc2ms($tc)
    {
        $tab = explode(':', $tc);
        $durMS = $tab[0] * 60 * 60 * 1000 + $tab[1] * 60 * 1000 + floatval($tab[2]) * 1000;

        return $durMS;
    }

    /**
     * @param int $ms
     * @param string $_separator
     * @return string
     */
    public static function ms2tc($ms, $_separator = '.', $isHoursPaddingEnabled = true)
    {
        return parent::ms2tc($ms, $_separator, false);
    }

    /**
     * Get the full timecode of the entry
     *
     * @return string
     */
    public function getTimeCodeString()
    {
        return $this->start.','.$this->stop;
    }
}
