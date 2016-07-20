<?php

namespace Captioning\Format;

use Captioning\File;
use Captioning\FileInterface;

class TtmlFile extends File
{
    const TIMEBASE_MEDIA = 0;
    const TIMEBASE_SMPTE = 1;
    const TIMEBASE_CLOCK = 2;

    private $timeBase;

    private $tickRate;

    private $styles = array();

    private $regions = array();

    /**
     * @param string $_timeBase
     * @return TtmlFile
     */
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

    /**
     * @param string $_tickRate
     */
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
        foreach ($head->styling->style as $style) {
            $styleData = $this->parseAttributes($style);
            $this->styles[$styleData['id']] = $styleData;
        }

        // parsing regions
        $regions = $head->layout->region;
        foreach ($regions as $region) {
            $regionData = $this->parseAttributes($region);
            $this->regions[$regionData['id']] = $regionData;

            if ($region->style) {
                $regionAttr = array();
                foreach ($region->style as $regionStyle) {
                    $regionAttr = array_merge($regionAttr, $this->parseAttributes($regionStyle));
                }
                $this->regions[$regionData['id']] = array_merge($this->regions[$regionData['id']], $regionAttr);
            }
        }

        // parsing cues
        $this->parseCues($xml->body);

        return $this;
    }

    public function buildPart($_from, $_to)
    {
        // TODO: Implement buildPart() method.
        return $this;
    }

    /**
     * @param TtmlCue $_mixed
     * @return FileInterface
     */
    public function addCue($_mixed, $_start = null, $_stop = null)
    {
        if (is_object($_mixed) && get_class($_mixed) === self::getExpectedCueClass($this)) {
            if (null !== $_mixed->getStyle() && !isset($this->styles[$_mixed->getStyle()])) {
                throw new \InvalidArgumentException(sprintf('Invalid cue style "%s"', $_mixed->getStyle()));
            }
            if (null !== $_mixed->getRegion() && !isset($this->regions[$_mixed->getRegion()])) {
                throw new \InvalidArgumentException(sprintf('Invalid cue region "%s"', $_mixed->getRegion()));
            }
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

    public function getRegions()
    {
        return $this->regions;
    }

    public function getRegion($_region_id)
    {
        if (!isset($this->regions[$_region_id])) {
            throw new \InvalidArgumentException;
        }

        return $this->regions[$_region_id];
    }

    private function parseAttributes($_node, $_namespace = 'tts')
    {
        $attributes = array();

        foreach ($_node->attributes($_namespace, true) as $property => $value) {
            $attributes[(string)$property] = (string)$value;
        }

        if ($_node->attributes('xml', true)->id) {
            $attributes['id'] = (string)$_node->attributes('xml', true)->id;
        }

        return $attributes;
    }

    private function parseCues($_xml)
    {
        $start = '';
        $stop = '';
        $startMS = 0;
        $stopMS = 0;
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
            $cue->setId((string)$p->attributes('xml', true)->id);

            if ($p->attributes()->style) {
                $cue->setStyle((string)$p->attributes()->style);
            }
            if ($p->attributes()->region) {
                $cue->setRegion((string)$p->attributes()->region);
            }

            $this->addCue($cue);
        }
    }
}
