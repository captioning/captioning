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
    public static function tc2ms($_timecode);

    /**
     * Converts milliseconds into subrip timecode format
     *
     * @param  int    $_ms
     * @return string
     */
    public static function ms2tc($_ms);
}
