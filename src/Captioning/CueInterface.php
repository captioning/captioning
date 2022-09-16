<?php

namespace Captioning;

interface CueInterface
{
    /**
     * Converts timecode format into milliseconds
     *
     * @param  string $_timecode timecode as string
     * @return int
     */
    public static function tc2ms(string $_timecode): int;

    /**
     * Converts milliseconds into subrip timecode format
     *
     * @param  int    $_ms
     * @return string
     */
    public static function ms2tc(int $_ms): string;
}
