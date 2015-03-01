<?php

namespace Captioning\Format;

use Captioning\File;

class TtmlFile extends File
{
    const TIMEBASE_MEDIA = 0;
    const TIMEBASE_SMPTE = 1;
    const TIMEBASE_CLOCK = 2;

    private $timeBase;

    private $tickRate;

    private $styles = array();

    private $regions = array();

    public function setTimeBase($_timeBase)
    {
        $matchingTable = array(
            'media' => self::TIMEBASE_MEDIA,
            'smpte' => self::TIMEBASE_SMPTE,
            'clock' => self::TIMEBASE_CLOCK
        );

        if (isset($matchingTable[$_timeBase])) {
            $_timeBase = $matchingTable[$_timeBase];
        }

        if (!in_array($_timeBase, array(self::TIMEBASE_MEDIA, self::TIMEBASE_SMPTE, self::TIMEBASE_CLOCK))) {
            throw new \InvalidArgumentException;
        }

        $this->timeBase = $_timeBase;

        return $this;
    }

    public function getTimeBase()
    {
        return $this->timeBase;
    }

    public function setTickRate($_tickRate)
    {
        $this->tickRate = $_tickRate;
    }

    public function getTickRate()
    {
        return $this->tickRate;
    }

    public function parse()
    {
        $xml = simplexml_load_string($this->fileContent);

        // parsing headers
        $this->setTimeBase((string)$xml->attributes('ttp', true)->timeBase);
        $this->setTickRate((string)$xml->attributes('ttp', true)->tickRate);

        $head = $xml->head;

        // parsing styles
        $this->styles = $this->parseAttributes($head->styling->style);

        // parsing regions
        $this->regions = $this->parseAttributes($head->layout->region);

        // parsing cues
        $this->parseCues($xml->body);
    }

    private function parseAttributes($_node, $_namespace = 'tts')
    {
        $res = array();

        foreach($_node as $child) {
            $attributes = array();
            $id         = (string)$child->attributes('xml', true)->id;

            foreach($child->attributes($_namespace, true) as $property => $value) {
                $attributes[(string)$property] = (string)$value;
            }

            $res[$id] = $attributes;
        }

        return $res;
    }

    private function parseCues($_xml)
    {
        foreach ($_xml->div->p as $p) {
            if (self::TIMEBASE_MEDIA === $this->timeBase) {
                $start   = (string)$p->attributes()->begin;
                $stop    = (string)$p->attributes()->end;
                $startMS = (int)rtrim($start, 't') / $this->tickRate * 1000;
                $stopMS  = (int)rtrim($stop, 't') / $this->tickRate * 1000;
            }

            $text = $p->asXml();

            $text = preg_replace('#^<p[^>]+>(.+)</p>$#isU', '$1', $text);

            $cue = new TtmlCue($start, $stop, $text);

            $cue->setStartMS($startMS);
            $cue->setStopMS($stopMS);

            $cue->setStyle((string)$p->attributes()->style);
            $cue->setId((string)$p->attributes('xml', true)->id);

            $this->addCue($cue);
        }
    }

    public function buildPart($_from, $_to)
    {
        // TODO: Implement buildPart() method.
    }

    public function addCue($_mixed, $_start = null, $_stop = null)
    {
        if (__NAMESPACE__.'\TtmlCue' === get_class($_mixed)
            && null !== $_mixed->getStyle() && !isset($this->styles[$_mixed->getStyle()])) {
            throw new \InvalidArgumentException(sprintf('Invalid cue style "%s"', $_mixed->getStyle()));
        }

        return parent::addCue($_mixed, $_start, $_stop);
    }

    public function getStyles()
    {
        return $this->styles;
    }

    public function getStyle($_style_id)
    {
        if (!isset($this->styles[$_style_id])) {
            throw new \InvalidArgumentException;
        }

        return $this->styles[$_style_id];
    }


} 