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
    /* fallback converter in case specific converter isn't implemented */
    public static function defaultConverter(FileInterface $_file, $_convertTo)
    {
        $subtitleClass = __NAMESPACE__.'\\Format\\'.ucfirst($_convertTo).'File';

        if (!class_exists($subtitleClass)) {
            throw new \InvalidArgumentException(sprintf('Unable to convert to "%s", this format does not exists.', $_convertTo));
        }

        $newSub        = new $subtitleClass();
        $cueClass      = File::getExpectedCueClass($newSub);

        foreach ($_file->getCues() as $cue) {
            $newSub->addCue($cue->getText(), $cueClass::ms2tc($cue->getStartMS()), $cueClass::ms2tc($cue->getStopMS()));
        }

        return $newSub;
    }

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

                // global cue style
                $text = self::applyTtmlStyles($text, $_ttml->getStyle($cue->getStyle()));

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

                        $textForReplace = self::applyTtmlStyles($spanText, $spanStyle);

                        if ($textForReplace != $spanText) {
                            $text = str_replace($spanStr, $textForReplace, $text);
                        }
                    }
                }
            }

            if (null !== $cue->getRegion()) {
                // cue region style
                $text = self::applyTtmlStyles($text, $_ttml->getRegion($cue->getRegion()));
            }

            $text = str_ireplace(array('<br>', '<br/>', '<br />'), SubripFile::UNIX_LINE_ENDING, $text);
            $text = preg_replace('#<\/?span[^>]*>#i', '', $text);

            $cleaningPatterns = array(
                '</i>'.SubripFile::UNIX_LINE_ENDING.'<i>',
                '</b>'.SubripFile::UNIX_LINE_ENDING.'<b>',
                '</u>'.SubripFile::UNIX_LINE_ENDING.'<u>'
            );
            $text = html_entity_decode(str_ireplace($cleaningPatterns, SubripFile::UNIX_LINE_ENDING, $text));

            $srt->addCue($text, SubripCue::ms2tc($cue->getStartMS()), SubripCue::ms2tc($cue->getStopMS()));
        }

        return $srt;
    }

    private static function applyTtmlStyles($text, array $styles)
    {
        if (isset($styles['fontStyle']) && 'italic' === $styles['fontStyle']) {
            $text = '<i>'.$text.'</i>';
        }
        if (isset($styles['fontWeight']) && 'bold' === $styles['fontWeight']) {
            $text = '<b>'.$text.'</b>';
        }
        if (isset($styles['textDecoration']) && 'underline' === $styles['textDecoration']) {
            $text = '<u>'.$text.'</u>';
        }

        return $text;
    }
}
