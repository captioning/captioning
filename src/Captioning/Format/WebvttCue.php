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

    public function getSetting($_name)
    {
        return isset($this->settings[$_name]) ? $this->settings[$_name] : false;
    }

    public function getSettings()
    {
        return $this->settings;
    }
}
