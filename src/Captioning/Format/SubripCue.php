<?php

namespace Captioning\Format;

use Captioning\Cue;

class SubripCue extends Cue
{
    public static function tc2ms($tc)
    {
        $tab = explode(':', $tc);
        $durMS = $tab[0]*60*60*1000 + $tab[1]*60*1000 + floatval(str_replace(',', '.', $tab[2]))*1000;

        return $durMS;
    }
    
    public static function ms2tc($ms, $_separator = ',')
    {
        $tc_ms = round((($ms / 1000) - intval($ms / 1000)) * 1000);
        $x = $ms / 1000;
        $tc_s = intval($x % 60);
        $x /= 60;
        $tc_m = intval($x % 60);
        $x /= 60;
        $tc_h = intval($x % 24);

        $timecode = str_pad($tc_h, 2, '0', STR_PAD_LEFT).':'
            .str_pad($tc_m, 2, '0', STR_PAD_LEFT).':'
            .str_pad($tc_s, 2, '0', STR_PAD_LEFT).$_separator
            .str_pad($tc_ms, 3, '0', STR_PAD_LEFT);

        return $timecode;
    }

    public function getText($_stripTags = false, $_stripBasic = false, $_replacements = array())
    {
        if ($_stripTags) {
            return $this->getStrippedText($_stripBasic, $_replacements);
        } else {
            return $this->text;
        }
    }

    /**
     * Return the text without Advanced SSA tags
     *
     * @param boolean $_stripBasic If true, <i>, <b> and <u> tags will be stripped
     * @param array $_replacements
     * @return string
     */
    public function getStrippedText($_stripBasic = false, $_replacements = array())
    {
        if ($_stripBasic) {
            $text = strip_tags($this->text);
        } else {
            $text = $this->text;
        }

        $patterns = "/{[^}]+}/";
        $repl = "";
        $text = preg_replace($patterns, $repl, $text);

        if (count($_replacements) > 0) {
            $text = str_replace(array_keys($_replacements), array_values($_replacements), $text);
            $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        }

        return $text;
    }

    /**
     * Get the full timecode of the entry
     *
     * @return string
     */
    public function getTimeCodeString()
    {
        return $this->start.' --> '.$this->stop;
    }

    public function strlen()
    {
        return mb_strlen($this->getText(true, true), 'UTF-8');
    }

    public function getReadingSpeed()
    {
        $dur = $this->getDuration();
        $dur = ($dur <= 500) ? 501 : $dur;

        return ($this->strlen() * 1000) / ($dur - 500);
    }
}
