<?php

namespace Captioning\Format;

use Captioning\Cue;
use Captioning\CueInterface;

class SubstationalphaCue extends Cue
{
    private $layer;
    private $style;
    private $name;
    private $marginL;
    private $marginR;
    private $marginV;
    private $effect;

    public function __construct($_start, $_stop, $_text = null, $_layer = 0, $_style = 'Default', $_name = '', $_marginL = '0000', $_marginR = '0000', $_marginV = '0000', $_effect = '')
    {
        parent::__construct($_start, $_stop, $_text);

        $this->layer   = $_layer;
        $this->style   = $_style;
        $this->name    = $_name;
        $this->marginL = $_marginL;
        $this->marginR = $_marginR;
        $this->marginV = $_marginV;
        $this->effect  = $_effect;
    }

    public static function tc2ms(string $_timecode): int
    {
        return SubripCue::tc2ms($_timecode.'0');
    }

    public static function ms2tc(int $_ms, string $_separator = ',', $isHoursPaddingEnabled = true): string
    {
        return parent::ms2tc($_ms, '.', false);
    }

    /**
     * @param string $_text
     * @return SubstationalphaCue
     */
    public function setText(string $_text): Cue
    {
        $this->text = preg_replace('#\r\n|\r|\n#', '\N', trim($_text));

        return $this;
    }

    public function getLayer()
    {
        return $this->layer;
    }
    public function getStyle()
    {
        return $this->style;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getMarginL()
    {
        return $this->marginL;
    }
    public function getMarginR()
    {
        return $this->marginR;
    }
    public function getMarginV()
    {
        return $this->marginV;
    }
    public function getEffect()
    {
        return $this->effect;
    }

    public function setLayer($_layer)
    {
        $this->layer = $_layer;
    }
    public function setStyle($_style)
    {
        $this->style = $_style;
    }
    public function setName($_name)
    {
        $this->name = $_name;
    }
    public function setMarginL($_marginL)
    {
        $this->marginL = $_marginL;
    }
    public function setMarginR($_marginR)
    {
        $this->marginR = $_marginR;
    }
    public function setMarginV($_marginV)
    {
        $this->marginV = $_marginV;
    }
    public function setEffect($_effect)
    {
        $this->effect = $_effect;
    }

    public function __toString(): string
    {
        $params = [
            $this->layer,
            $this->start,
            $this->stop,
            $this->style,
            $this->name,
            $this->marginL,
            $this->marginR,
            $this->marginV,
            $this->effect,
            $this->text
        ];

        $buffer = 'Dialogue: ';
        $buffer .= implode(',', $params);

        return $buffer;
    }

    /**
     * @param int $tc_ms
     * @return string
     */
    protected static function getLastTimeCodePart(int $tc_ms): string
    {
        return substr(str_pad($tc_ms, 3, '0', STR_PAD_LEFT), 0, -1);
    }
}
