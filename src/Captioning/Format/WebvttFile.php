<?php

namespace Captioning\Format;

use Captioning\File;

class WebvttFile extends File
{
    const TIMECODE_PATTERN = '#^([0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}) --> ([0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}) ?(.*)$#';

    protected $regions = array();

    public function parse()
    {
        $handle = fopen($this->filename, "r");
        $parsing_errors = array();

        if ($handle) {
            $case = 'header';
            $i = 1;
            while (($line = fgets($handle)) !== false) {
                // checking header
                if ($case === 'header' && trim($line) != 'WEBVTT') {
                    $parsing_errors[] = 'Missing "WEBVTT" at the beginning of the file';
                } elseif ($case === 'header') {
                    $case = 'region';
                    continue;
                }

                if ($case !== 'header') {
                    // parsing regions
                    if ($case === 'region' && substr($line, 0, 7) == 'Region:') {
                        $this->addRegion(WebvttRegion::parseFromString($line));
                        continue;
                    }

                    if ($case === 'region' && trim($line) === '') {
                        $case = 'body';
                        continue;
                    }

                    if ($case === 'body') {
                        // parsing notes
                        if (substr($line, 0, 4) === 'NOTE') {
                            if (trim($line) === 'NOTE') {
                                $note = $this->lineEnding;
                            } else {
                                $note = trim(ltrim($line, 'NOTE ')).$this->lineEnding;
                            }
                            // note continues until there is a blank line
                            while (trim($line = fgets($handle)) !== '') {
                                $note .= trim($line).$this->lineEnding;
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

                                $line = fgets($handle);
                                $matches = array();
                                $timecode_match = preg_match(self::TIMECODE_PATTERN, $line, $matches);
                            }

                            if (!$timecode_match) {
                                $parsing_errors[] = 'Malformed cue detected at line '.$i;
                            } else {
                                $start = $matches[1];
                                $stop = $matches[2];
                                $settings = trim($matches[3]);
                            }

                            // cue continues until there is a blank line
                            while (trim($line = fgets($handle)) !== '') {
                                $text .= trim($line).$this->lineEnding;
                            }

                            // make the cue object and add it to the file
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
        } else {
            throw new \Exception('Could not read the file "'.$this->filename.'".');
        }

        fclose($handle);

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
            $_to = $this->getCuesCount()-1;
        }

        for ($j = $_from; $j <= $_to; $j++) {
            $buffer .= $this->getCue($j).$this->lineEnding;
        }

        $this->fileContent = $buffer;

        return $this;
    }
}
