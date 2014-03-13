<?php

namespace Captioning\Format;

use Captioning\File;

class SubripFile extends File
{
    const PATTERN = '#[0-9]+(?:\r\n|\r|\n)([0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3}) --> ([0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3})(?:\r\n|\r|\n)((?:.*(?:\r\n|\r|\n))*?)(?:\r\n|\r|\n)#';

    public function parse()
    {
        $matches = array();
        $res = preg_match_all(self::PATTERN, $this->file_content, $matches);

        if (!$res || $res == 0) {
            throw new \Exception($this->filename.' is not a proper .srt file.');
        }

        $entries_count = sizeof($matches[1]);

        for ($i = 0; $i < $entries_count; $i++) {
            $cue = new SubripCue($matches[1][$i], $matches[2][$i], $matches[3][$i]);
            $this->addCue($cue);
        }

        return $this;
    }

    /**
     * Builds file content
     *
     * @param boolean $_stripTags If true, {\...} tags will be stripped
     * @param boolean $_stripBasic If true, <i>, <b> and <u> tags will be stripped
     * @param array $_replacements
     */
    public function build($_stripTags = false, $_stripBasic = false, $_replacements = array())
    {
        $this->buildPart(0, $this->getCuesCount()-1, $_stripTags, $_stripBasic, $_replacements);

        return $this;
    }

    /**
     * Builds file content from entry $_from to entry $_to
     *
     * @param int $_from Id of the first entry
     * @param int $_to Id of the last entry
     * @param boolean $_stripTags If true, {\...} tags will be stripped
     * @param boolean $_stripBasic If true, <i>, <b> and <u> tags will be stripped
     * @param array $_replacements
     */
    public function buildPart($_from, $_to, $_stripTags = false, $_stripBasic = false, $_replacements = array())
    {
        $this->sortCues();
        
        $i = 1;
        $buffer = "";
        if ($_from < 0 || $_from >= $this->getCuesCount()) {
            $_from = 0;
        }

        if ($_to < 0 || $_to >= $this->getCuesCount()) {
            $_to = $this->getCuesCount()-1;
        }

        for ($j = $_from; $j <= $_to; $j++) {
            $buffer .= $i."\r\n";
            $buffer .= $this->getCue($j)->getTimeCodeString()."\r\n";
            $buffer .= $this->getCue($j)->getText($_stripTags, $_stripBasic, $_replacements)."\r\n";
            $buffer .= "\r\n";
            $i++;
        }
        
        $this->file_content = $buffer;

        return $this;
    }
}
