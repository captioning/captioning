<?php

namespace Captioning\Format;

use Captioning\Cue;

class WebvttCue extends Cue
{
    protected $identifier = null;
    protected $note       = null;
    protected $settings   = array();

    public static function tc2ms($tc)
    {
        return SubripCue::tc2ms($tc);
    }

    public static function ms2tc($ms, $_separator = '.', $isHoursPaddingEnabled = true)
    {
        return SubripCue::ms2tc($ms, $_separator, $isHoursPaddingEnabled);
    }

    public function setSetting($_name, $_value)
    {
        if (self::checkSetting($_name, $_value)) {
            $this->settings[$_name] = $_value;
        }

        return $this;
    }

    public function setNote($_note)
    {
        $this->note = rtrim($_note);

        return $this;
    }

    public function setIdentifier($_identifier)
    {
        $this->identifier = $_identifier;

        return $this;
    }

    public function getSetting($_name)
    {
        return isset($this->settings[$_name]) ? $this->settings[$_name] : false;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public static function checkSetting($_name, $_value)
    {
        switch ($_name) {
            case 'region':
                return true;
            case 'vertical':
                return in_array($_value, array('rl', 'lr'));
            case 'line':
                return preg_match('#^[0-9][0-9]?%$|^100%$#', $_value) || in_array($_value, array('start', 'middle', 'end'));
            case 'size':
                return preg_match('#^[0-9][0-9]?%$|^100%$#', $_value);
            case 'position':
                return in_array($_value, array('start', 'middle', 'end'));
            case 'align':
                return in_array($_value, array('start', 'middle', 'end', 'left', 'right'));
            default:
                return false;
        }
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

    public function getSettingsString()
    {
        $buffer = '';
        foreach ($this->settings as $setting => $value) {
            $buffer .= $setting.':'.$value.' ';
        }

        return trim($buffer);
    }

    public function __toString()
    {
        $buffer = '';

        if ($this->note !== null) {
            $buffer .= 'NOTE '.$this->note.$this->lineEnding.$this->lineEnding;
        }

        if ($this->identifier !== null) {
            $buffer .= $this->identifier.$this->lineEnding;
        }

        $buffer .= $this->getTimeCodeString();

        if (count($this->settings) > 0) {
            $buffer .= ' '.$this->getSettingsString();
        }

        $buffer .= $this->lineEnding;
        $buffer .= $this->getText().$this->lineEnding;

        return $buffer;
    }
}
