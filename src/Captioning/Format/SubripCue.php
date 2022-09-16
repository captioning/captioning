<?php

namespace Captioning\Format;

use Captioning\Cue;

class SubripCue extends Cue
{
    public static function tc2ms(string $_timecode): int
    {
        $tab = array_reverse(explode(':', $_timecode));
        $tab[2] = $tab[2] ?? 0;

        return $tab[2] * 60 * 60 * 1000 + $tab[1] * 60 * 1000 + (float)str_replace(',', '.', $tab[0]) * 1000;
    }

    /**
     * @param int $_ms
     * @param string $_separator
     * @return string
     */
    public static function ms2tc(int $_ms, string $_separator = ',', $isHoursPaddingEnabled = true): string
    {
        return parent::ms2tc($_ms, $_separator, $isHoursPaddingEnabled);
    }

    public function getText($_stripTags = false, $_stripBasic = false, $_replacements = []): string
    {
        parent::getText();

        if ($_stripTags) {
            return $this->getStrippedText($_stripBasic, $_replacements);
        }

        return $this->text;
    }

    /**
     * Return the text without Advanced SSA tags
     *
     * @param boolean $_stripBasic If true, <i>, <b> and <u> tags will be stripped
     * @param array $_replacements
     * @return string
     */
    public function getStrippedText($_stripBasic = false, array $_replacements = []): string
    {
        $text = $this->text;

        if ($_stripBasic) {
            $text = strip_tags($text);
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
    public function getTimeCodeString(): string
    {
        return $this->start.' --> '.$this->stop;
    }

    public function strlen(): int
    {
        return mb_strlen($this->getText(true, true), 'UTF-8');
    }
}
