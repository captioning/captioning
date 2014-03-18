<?php

namespace Captioning;

use Captioning\Format\SubripFile;
use Captioning\Format\SubripCue;
use Captioning\Format\WebvttFile;
use Captioning\Format\WebvttCue;
use Captioning\Format\SubstationalphaFile;
use Captioning\Format\SubstationalphaCue;

class Converter
{
    /* subrip converters */
    public static function subrip2webvtt(SubripFile $_srt)
    {
        $vtt = new WebvttFile();
        foreach ($_srt->getCues() as $cue) {
            $vtt->addCue($cue->getText(true), SubripCue::ms2tc($cue->getStartMS(), '.'), SubripCue::ms2tc($cue->getStopMS(), '.'));
        }

        return $vtt;
    }

    public static function subrip2substationalpha(SubripFile $_srt)
    {
        $ass = new SubstationalphaFile();
        foreach ($_srt->getCues() as $cue) {
            $search = array("\r\n", "\r", "\n", '<i>', '</i>', '<b>', '</b>', '<u>', '</u>');
            $replace = array('\N', '\N', '\N', '{\i1}', '{\i0}', '{\b1}', '{\b0}', '{\u1}', '{\u0}');
            $text = str_replace($search, $replace, $cue->getText());

            $search_regex = array(
                '#<font color="?\#?([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})"?>(.+)</font>#is'
            );
            $replace_regex = array(
                '{\c&H$3$2$1&}$4'
            );
            $text = preg_replace($search_regex, $replace_regex, $text);

            $ass->addCue($text, SubstationalphaCue::ms2tc($cue->getStartMS()), SubstationalphaCue::ms2tc($cue->getStopMS()));
        }

        return $ass;
    }

    /* webvtt converters */
    public static function webvtt2subrip(WebvttFile $_vtt)
    {
        $srt = new SubripFile();
        foreach ($_vtt->getCues() as $cue) {
            $srt->addCue($cue->getText(), SubripCue::ms2tc($cue->getStartMS()), SubripCue::ms2tc($cue->getStopMS()));
        }

        return $srt;
    }

    public static function webvtt2substationalpha(SubripFile $_srt)
    {
        return self::subrip2substationalpha($_srt);
    }

    /* substation alpha converters */
}
