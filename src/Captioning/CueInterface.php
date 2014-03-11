<?php

namespace Captioning;

interface CueInterface
{
    /**
     * Converts timecode format into milliseconds
     *
     * @param  string $tc timecode as string
     * @return int
     */
    public static function tc2ms($_timecode);

    /**
     * Converts milliseconds into subrip timecode format
     *
     * @param  int    $ms
     * @return string
     */
    public static function ms2tc($_ms);
}
