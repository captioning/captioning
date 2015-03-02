<?php

namespace Captioning;

use Captioning\Format\SubripFile;
use Captioning\Format\SubripCue;
use Captioning\Format\TtmlFile;
use Captioning\Format\WebvttFile;
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
            $search  = array("\r\n", "\r", "\n", '<i>', '</i>', '<b>', '</b>', '<u>', '</u>');
            $replace = array('\N', '\N', '\N', '{\i1}', '{\i0}', '{\b1}', '{\b0}', '{\u1}', '{\u0}');
            $text    = str_replace($search, $replace, $cue->getText());

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

    public static function webvtt2substationalpha(WebvttFile $_vtt)
    {
        return self::subrip2substationalpha(self::webvtt2subrip($_vtt));
    }

    /* substation alpha converters */
    public static function substationalpha2subrip(SubstationalphaFile $_ass)
    {
        $srt = new SubripFile();
        foreach ($_ass->getCues() as $cue) {
            $search  = array('\N', '\N', '\N', '{\i1}', '{\i0}', '{\b1}', '{\b0}', '{\u1}', '{\u0}');
            $replace = array("\r\n", "\r", "\n", '<i>', '</i>', '<b>', '</b>', '<u>', '</u>');
            $text    = str_replace($search, $replace, $cue->getText());

            $search_regex = array(
                '#{\\c&H([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})\}(.+)#is'
            );
            $replace_regex = array(
                '<font color="#$3$2$1">$4</font>'
            );
            $text = preg_replace($search_regex, $replace_regex, $text);

            $srt->addCue($text, SubripCue::ms2tc($cue->getStartMS()), SubripCue::ms2tc($cue->getStopMS()));
        }

        return $srt;
    }

    public static function substationalpha2webvtt(SubstationalphaFile $_ass)
    {
        return self::subrip2webvtt(self::substationalpha2subrip($_ass));
    }

    /* ttml converters */
    public static function ttml2subrip(TtmlFile $_ttml)
    {
        $srt = new SubripFile();
        foreach ($_ttml->getCues() as $cue) {
            $text = $cue->getText();

            if (null !== $cue->getStyle()) {
                $cueStyle = $_ttml->getStyle($cue->getStyle());
                // global cue style
                if (isset($cueStyle['fontStyle']) && 'italic' === $cueStyle['fontStyle']) {
                    $text = '<i>' . $text . '</i>';
                }
                if (isset($cueStyle['fontWeight']) && 'bold' === $cueStyle['fontWeight']) {
                    $text = '<b>' . $text . '</b>';
                }
                if (isset($cueStyle['textDecoration']) && 'underline' === $cueStyle['textDecoration']) {
                    $text = '<u>' . $text . '</u>';
                }

                // span styles
                $matches = array();
                preg_match_all('#<span[^>]*style="([^>"]+)"[^>]*>(.+)</span>#isU', $text, $matches);
                $spanCount = count($matches[0]);
                if ($spanCount > 0) {
                    for ($i = 0; $i < $spanCount; $i++) {
                        $spanStr     = $matches[0][$i];
                        $spanStyleId = $matches[1][$i];
                        $spanText    = $matches[2][$i];

                        $spanStyle = $_ttml->getStyle($spanStyleId);

                        if (isset($spanStyle['fontStyle']) && 'italic' === $spanStyle['fontStyle']) {
                            $text = str_replace($spanStr, '<i>' . $spanText . '</i>', $text);
                        }
                        if (isset($spanStyle['fontWeight']) && 'bold' === $spanStyle['fontWeight']) {
                            $text = str_replace($spanStr, '<b>' . $spanText . '</b>', $text);
                        }
                        if (isset($spanStyle['textDecoration']) && 'underline' === $spanStyle['textDecoration']) {
                            $text = str_replace($spanStr, '<u>' . $spanText . '</u>', $text);
                        }
                    }
                }
            }

            if (null !== $cue->getRegion()) {
                $cueRegion = $_ttml->getRegion($cue->getRegion());
                // global cue style
                if (isset($cueRegion['fontStyle']) && 'italic' === $cueRegion['fontStyle']) {
                    $text = '<i>' . $text . '</i>';
                }
                if (isset($cueRegion['fontWeight']) && 'bold' === $cueRegion['fontWeight']) {
                    $text = '<b>' . $text . '</b>';
                }
                if (isset($cueRegion['textDecoration']) && 'underline' === $cueRegion['textDecoration']) {
                    $text = '<u>' . $text . '</u>';
                }
            }

            $text = str_ireplace(array('<br>', '<br/>', '<br />'), SubripFile::UNIX_LINE_ENDING, $text);

            $cleaningPatterns = array(
                '</i>'.SubripFile::UNIX_LINE_ENDING.'<i>',
                '</b>'.SubripFile::UNIX_LINE_ENDING.'<b>',
                '</u>'.SubripFile::UNIX_LINE_ENDING.'<u>'
            );
            $text = str_ireplace($cleaningPatterns, SubripFile::UNIX_LINE_ENDING, $text);

            $srt->addCue($text, SubripCue::ms2tc($cue->getStartMS()), SubripCue::ms2tc($cue->getStopMS()));
        }

        return $srt;
    }
}
