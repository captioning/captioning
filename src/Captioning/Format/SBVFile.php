<?php

namespace Captioning\Format;

use Captioning\File;

class SBVFile extends File
{
    const PATTERN_TIMECODE = '/(\d{1,2}:\d{2}:\d{2}.\d{3}),(\d{1,2}:\d{2}:\d{2}.\d{3})/';

    protected $lineEnding = File::WINDOWS_LINE_ENDING;

    public function parse()
    {
        $matches = array();
        $res = preg_match_all(self::PATTERN_TIMECODE, $this->fileContent, $matches);

        if (!$res || $res == 0) {
            throw new \Exception($this->filename.' is not a proper .sbv file.');
        }

        $lines = explode($this->lineEnding, $this->fileContent);

        $step = 'time';
        foreach ($lines as $lineNumber => $line) {
            switch ($step) {
                case 'time':
                    $timeline = explode(',', $line);
                    if (count($timeline) !== 2) {
                        throw new \Exception($this->filename." is not a proper .sbv file. (Invalid timestamp delimiter at line ".$lineNumber.")");
                    }
                    $cueStart = trim($timeline[0]);
                    $cueStop = trim($timeline[1]);

                    $cueText = array();
                    $step = 'text';
                    break;
                case 'text':
                    $cueText[] = $line;
                    if ($lineNumber === count($lines) - 1 || ($line === '' && $lines[$lineNumber+1] !== '')) {
                        $step = 'end';
                    } else {
                        break;
                    }
                case 'end':
                    $cue = new SBVCue($cueStart, $cueStop, implode($this->lineEnding, $cueText));
                    $cue->setLineEnding($this->lineEnding);
                    $this->addCue($cue);

                    $step = 'time';
                    break;
            }
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
