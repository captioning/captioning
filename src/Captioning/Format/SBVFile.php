<?php

namespace Captioning\Format;

use Captioning\File;

class SBVFile extends File
{
    const PATTERN = '#([0-9]{1,2}:[0-9]{2}:[0-9]{2}.[0-9]{3}),([0-9]{1,2}:[0-9]{2}:[0-9]{2}.[0-9]{3})(?:\r\n|\r|\n)((?:.*(?:\r\n|\r|\n))*?)(?:\r\n|\r|\n)#';

    protected $lineEnding = File::WINDOWS_LINE_ENDING;

    public function parse()
    {
        $matches = array();
        $res = preg_match_all(self::PATTERN, $this->fileContent, $matches);

        if (!$res || $res == 0) {
            throw new \Exception($this->filename.' is not a proper .sbv file.');
        }

        $entries_count = count($matches[1]);

        for ($i = 0; $i < $entries_count; $i++) {
            $cue = new SBVCue($matches[1][$i], $matches[2][$i], $matches[3][$i]);
            $cue->setLineEnding($this->lineEnding);
            $this->addCue($cue);
        }

        return $this;
    }

    /**
     * Builds file content from entry $_from to entry $_to
     *
     * @param int $_from Id of the first entry
     * @param int $_to Id of the last entry
     * @return SBVFile
     */
    public function buildPart($_from, $_to)
    {
        $this->sortCues();

        $buffer = '';
        if ($_from < 0 || $_from >= $this->getCuesCount()) {
            $_from = 0;
        }

        if ($_to < 0 || $_to >= $this->getCuesCount()) {
            $_to = $this->getCuesCount() - 1;
        }

        for ($j = $_from; $j <= $_to; $j++) {
            /** @var SBVCue $cue */
            $cue = $this->getCue($j);
            $buffer .= $cue->getTimeCodeString().$this->lineEnding;
            $buffer .= $cue->getText().$this->lineEnding;
            $buffer .= $this->lineEnding;
        }
        
        $this->fileContent = $buffer;

        return $this;
    }
}
