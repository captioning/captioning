<?php

namespace Captioning\Format;

use Captioning\Cue;

class SBVCue extends Cue
{
    public static function tc2ms(string $_timecode): int
    {
        $tab = explode(':', $_timecode);
        return $tab[0] * 60 * 60 * 1000 + $tab[1] * 60 * 1000 + (float) $tab[2] * 1000;
    }

    /**
     * @param int $_ms
     * @param string $_separator
     * @return string
     */
    public static function ms2tc(int $_ms, string $_separator = '.', $isHoursPaddingEnabled = true): string
    {
        return parent::ms2tc($_ms, $_separator, false);
    }

    /**
     * Get the full timecode of the entry
     *
     * @return string
     */
    public function getTimeCodeString(): string
    {
        return $this->start.','.$this->stop;
    }
}
