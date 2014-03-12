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

    public static function ms2tc($ms)
    {
        return SubripCue::ms2tc($ms, '.');
    }

    public function setSetting($_name, $_value)
    {
        $this->settings[$_name] = $_value;
    }

    public function setNote($_note)
    {
        $this->note = $_note;
    }

    public function setIdentifier($_identifier)
    {
        $this->identifier = $_identifier;
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

    /**
     * Get the full timecode of the entry
     *
     * @return string
     */
    public function getTimeCodeString()
    {
        return $this->start.' --> '.$this->stop;
    }

    public function __toString()
    {
        $buffer = '';
        if ($this->identifier !== null) {
            $buffer .= $this->identifier."\n";
        }
        
        $buffer .= $this->getTimeCodeString()."\n";
        $buffer .= $this->getText()."\n";

        return $buffer;
    }
}
