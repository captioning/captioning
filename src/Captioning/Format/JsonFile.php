<?php
/**
 * Created by PhpStorm.
 * User: delphiki
 * Date: 18/03/16
 * Time: 19:46
 */

namespace Captioning\Format;

use Captioning\File;

class JsonFile extends File
{
    public function parse()
    {
        $decodedContent = json_decode($this->fileContent, true);

        if (!isset($decodedContent['captions'])) {
            throw new \InvalidArgumentException('Invalid JSON subtitle');
        }

        if (count($decodedContent['captions']) > 0) {
            foreach ($decodedContent['captions'] as $c) {
                $cue = new JsonCue($c['startTime'], $c['startTime'] + $c['duration'], $c['content']);
                $cue->setDuration($c['duration']);
                $cue->setStartOfParagraph($c['startOfParagraph']);

                $this->addCue($cue);
            }
        }

        return $this;
    }

    public function buildPart($_from, $_to)
    {
        $this->sortCues();

        if ($_from < 0 || $_from >= $this->getCuesCount()) {
            $_from = 0;
        }

        if ($_to < 0 || $_to >= $this->getCuesCount()) {
            $_to = $this->getCuesCount() - 1;
        }


        $captions = array();
        for ($j = $_from; $j <= $_to; $j++) {
            /** @var JsonCue $cue */
            $cue = $this->getCue($j);

            $captions[] = array(
                'duration'         => $cue->getDuration(),
                'content'          => $cue->getText(),
                'startOfParagraph' => $cue->isStartOfParagraph(),
                'startTime'        => $cue->getStart(),
            );
        }

        $this->fileContent = json_encode(array('captions' => $captions));

        return $this;
    }

}