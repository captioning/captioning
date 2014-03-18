<?php

namespace Captioning\Format;

use Captioning\File;

class SubstationalphaFile extends File
{
    const PATTERN = '#Dialogue: ([0-9]),([0-9]:[0-9]{2}:[0-9]{2}.[0-9]{2}),([0-9]:[0-9]{2}:[0-9]{2}.[0-9]{2}),(.*),(.*),([0-9]{4}),([0-9]{4}),([0-9]{4}),([^,]*),(.+)#';

    protected $headers;
    protected $styles;
    protected $events;
    protected $comments;

    public function __construct($_filename = null, $_encoding = null)
    {
        $this->headers = array(
            'Title'                => '<untitled>',
            'Original Script'      => '<unknown>',
            'Original Translation' => null,
            'Original Editing'     => null,
            'Original Timing'      => null,
            'Synch Point'          => null,
            'Script Updated By'    => null,
            'Update Details'       => null,
            'ScriptType'           => 'v4.00+',
            'Collisions'           => 'Normal',
            'PlayResX'             => 384,
            'PlayResY'             => 288,
            'PlayDepth'            => 0,
            'Timer'                => '100.0',
            'WrapStyle'            => 0
        );

        $this->styles = array(
            'Name'            => 'Default',
            'Fontname'        => 'Arial',
            'Fontsize'        => 20,
            'PrimaryColour'   => '&H00FFFFFF',
            'SecondaryColour' => '&H00000000',
            'OutlineColour'   => '&H00000000',
            'BackColour'      => '&H00000000',
            'Bold'            => 0,
            'Italic'          => 0,
            'Underline'       => 0,
            'StrikeOut'       => 0,
            'ScaleX'          => 100,
            'ScaleY'          => 100,
            'Spacing'         => 0,
            'Angle'           => 0,
            'BorderStyle'     => 1,
            'Outline'         => 2,
            'Shadow'          => 0,
            'Alignment'       => 2,
            'MarginL'         => 15,
            'MarginR'         => 15,
            'MarginV'         => 15,
            'Encoding'        => 0
        );
        
        $this->events = array(
            'Layer', 'Start', 'End', 'Style', 'Name', 'MarginL', 'MarginR', 'MarginV', 'Effect', 'Text'
        );

        $this->comments = array();

        parent::__construct($_filename, $_encoding);
    }

    public function setHeader($_name, $_value)
    {
        if (isset($this->headers[$_name])) {
            $this->headers[$_name] = $_value;
        }
    }

    public function getHeader($_name)
    {
        return isset($this->headers[$_name]) ? $this->headers[$_name] : false;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setStyle($_name, $_value)
    {
        if (isset($this->styles[$_name])) {
            $this->styles[$_name] = $_value;
        }
    }

    public function getStyle($_name)
    {
        return isset($this->styles[$_name]) ? $this->styles[$_name] : false;
    }

    public function getStyles()
    {
        return $this->styles;
    }

    public function addComment($_comment)
    {
        $this->comments[] = $_comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function parse()
    {
        $handle = fopen($this->filename, "r");
        $parsing_errors = [];

        if ($handle) {
            while (($line = fgets($handle)) !== false) {

                // parsing headers
                if (strtolower(trim($line)) === '[script info]') {
                    while (trim($line = fgets($handle)) !== '') {
                        if ($line[0] == ';') {
                            $this->addComment(trim(ltrim($line, '; ')));
                        } else {
                            $tmp = explode(':', $line);
                            if (count($tmp) == 2) {
                                $this->setHeader(trim($tmp[0]), trim($tmp[1]));
                            }
                        }
                    }
                }

                // parsing styles
                if (strtolower(trim($line)) === '[v4+ styles]') {
                    $line = fgets($handle);
                    $tmp_styles = array();
                    $tmp = explode(':', $line);
                    if ($tmp[0] == 'Format') {
                        $tmp2 = explode(',', $tmp[1]);

                        foreach ($tmp2 as $s) {
                            $tmp_styles[trim($s)] = null;
                        }
                    } else {
                        return false;
                    }

                    $line = fgets($handle);
                    $tmp = explode(':', $line);
                    if ($tmp[0] == 'Style') {
                        $tmp2 = explode(',', $tmp[1]);
                        $i = 0;
                        foreach ($tmp_styles as $s => $v) {
                            $this->setStyle($s, trim($tmp2[$i]));
                            $i++;
                        }
                    } else {
                        return false;
                    }
                    break;
                }
            }
        }
        fclose($handle);

        // TODO: dynamic parsing if events format is not the default one
        $matches = array();
        preg_match_all(self::PATTERN, $this->file_content, $matches);
        for ($i=0; $i < count($matches[1]); $i++) {
            $cue = new SubstationalphaCue(
                $matches[2][$i],
                $matches[3][$i],
                $matches[10][$i],
                $matches[1][$i],
                $matches[4][$i],
                $matches[5][$i],
                $matches[6][$i],
                $matches[7][$i],
                $matches[8][$i],
                $matches[9][$i]
            );

            $this->addCue($cue);
        }
    }

    public function buildPart($_from, $_to)
    {
        // headers
        $buffer = '[Script Info]'."\n";
        foreach ($this->comments as $comment) {
            $buffer .= '; '.str_replace("\n", "\n; ", $comment)."\n";
        }
        foreach ($this->headers as $key => $value) {
            if ($value !==  null) {
                $buffer .= $key.': '.$value."\n";
            }
        }
        $buffer .= "\n";
                
        // styles
        $buffer .= '[v4+ Styles]'."\n";
        $buffer .= 'Format: '.implode(', ', array_keys($this->styles))."\n";
        $buffer .= 'Style: '.implode(', ', array_values($this->styles))."\n";
            
        // events (= cues)
        $buffer .= "\n";
        $buffer .= '[Events]'."\n";
        $buffer .= 'Format: '.implode(', ', $this->events)."\n";

        foreach ($this->cues as $cue) {
            $buffer .= $cue."\n";
        }

        $this->file_content = $buffer;
    }
}
