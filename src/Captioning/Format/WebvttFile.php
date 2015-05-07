<?php

namespace Captioning\Format;

use Captioning\File;

class WebvttFile extends File
{
    const TIMECODE_PATTERN = '#^([0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}) --> ([0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}) ?(.*)$#';

    protected $regions = array();

    public function parse()
    {
        $fileContentArray = $this->getFileContentAsArray();

        $parsing_errors = array();

        $case = 'header';
        $i = 1;
        while (($line = $this->getNextValueFromArray($fileContentArray)) !== false) {
            // checking header
            if ('header' === $case) {
                if (trim($line) == 'WEBVTT') {
                    $case = 'region';
                    continue;
                }
                $parsing_errors[] = 'Missing "WEBVTT" at the beginning of the file';
            }

            if ($case !== 'header') {

                if ('region' === $case) {
                    // parsing regions
                    if (strpos($line,'Region:') === 0) {
                        $this->addRegion(WebvttRegion::parseFromString($line));
                    } else if (trim($line) === '') {
                        $case = 'body';
                    }
                    continue;
                } else if ($case === 'body') {
                    // parsing notes
                    if (strpos($line, 'NOTE') === 0) {
                        $note = '';
                        if (trim($line) !== 'NOTE') {
                            $note = trim(ltrim($line, 'NOTE '));
                        }
                        $note .= $this->lineEnding;
                        // note continues until there is a blank line
                        while (trim($line = trim($this->getNextValueFromArray($fileContentArray))) !== '') {
                            $note .= $line.$this->lineEnding;
                            $i++;
                        }
                        continue;
                    }

                    // parsing cues
                    $id_match = !strstr($line, '-->') && trim($line) != '';
                    $matches = array();
                    $timecode_match = preg_match(self::TIMECODE_PATTERN, $line, $matches);
                    if ($id_match || $timecode_match) {
                        $id       = null;
                        $start    = null;
                        $stop     = null;
                        $settings = null;
                        $text     = '';

                        if ($id_match) {
                            $id = $line;
                            $line = $this->getNextValueFromArray($fileContentArray);
                            $matches = array();
                            $timecode_match = preg_match(self::TIMECODE_PATTERN, $line, $matches);
                        }

                        if ($timecode_match) {
                            $start = $matches[1];
                            $stop = $matches[2];
                            $settings = trim($matches[3]);
                        } else {
                            $parsing_errors[] = 'Malformed cue detected at line '.$i;
                        }

                        // cue continues until there is a blank line
                        while (trim($line = $this->getNextValueFromArray($fileContentArray)) !== '') {
                            $text .= trim($line).$this->lineEnding;
                        }

                        // make the cue object and add it to the file
                        $cue = $this->createCue($start, $stop, $text, $settings, $id);

                        if (!empty($note)) {
                            $cue->setNote($note);
                            unset($note);
                        }

                        $this->addCue($cue);
                        unset($cue);

                        continue;
                    }
                }
            }
            $i++;
        }

        if (count($parsing_errors) > 0) {
            throw new \Exception('The following errors were found while parsing the file:'."\n".print_r($parsing_errors, true));
        }

        return $this;
    }

    public function addRegion(WebvttRegion $_region)
    {
        $this->regions[] = $_region;

        return $this;
    }

    public function getRegion($_index)
    {
        if (!isset($this->regions[$_index])) {
            return;
        }

        return $this->regions[$_index];
    }

    public function getRegions()
    {
        return $this->regions;
    }

    public function buildPart($_from, $_to)
    {
        $this->sortCues();

        $buffer = "WEBVTT".$this->lineEnding;

        foreach ($this->regions as $region) {
            $buffer .= $region.$this->lineEnding;
        }
        $buffer .= $this->lineEnding;

        if ($_from < 0 || $_from >= $this->getCuesCount()) {
            $_from = 0;
        }

        if ($_to < 0 || $_to >= $this->getCuesCount()) {
            $_to = $this->getCuesCount() - 1;
        }

        for ($j = $_from; $j <= $_to; $j++) {
            $buffer .= $this->getCue($j).$this->lineEnding;
        }

        $this->fileContent = $buffer;

        return $this;
    }

    /**
     * @param string $start
     * @param string $stop
     * @param string $text
     * @param string $settings
     * @param string $id
     * @return WebvttCue
     */
    private function createCue($start, $stop, $text, $settings, $id)
    {
        $cue = new WebvttCue($start, $stop, $text);
        $tmp = explode(' ', trim($settings));
        foreach ($tmp as $setting) {
            $tmp2 = explode(':', $setting);

            if (count($tmp2) !== 2) {
                continue;
            }

            $cue->setSetting($tmp2[0], $tmp2[1]);
        }

        if ($id !== null) {
            $cue->setIdentifier($id);
        }

        return $cue;
    }
}
