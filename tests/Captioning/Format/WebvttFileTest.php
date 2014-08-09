<?php

namespace Captioning\Format;

class WebvttFileTest extends \PHPUnit_Framework_TestCase
{
    public function testAddingCuesBothWays()
    {
        $start = '01:02:03.456';
        $stop = '01:02:04.567';
        $text = 'Once upon a time';
        $cue = new WebvttCue($start, $stop, $text);

        $file = new WebvttFile();
        $file
            ->addCue($cue)
            ->addCue($text, $start, $stop)
        ;

        $this->assertEquals(2, $file->getCuesCount());
        $this->assertSame($file->getCue(0)->getText(), $file->getCue(1)->getText());
        $this->assertSame($file->getCue(0)->getStart(), $file->getCue(1)->getStart());
        $this->assertSame($file->getCue(0)->getStop(), $file->getCue(1)->getStop());
        $this->assertSame($file->getCue(0)->getStartMS(), $file->getCue(1)->getStartMS());
        $this->assertSame($file->getCue(0)->getStopMS(), $file->getCue(1)->getStopMS());
    }

    public function testIfAFileIsParsedProperly()
    {
        // example file from W3C spec
        $filename = __DIR__.'/../../Fixtures/example-1.vtt';
        $file = new WebvttFile($filename);

        // regions
        $this->assertEquals(2, count($file->getRegions()));
        $this->assertEquals('fred', $file->getRegion(0)->getId());
        $this->assertEquals('40%', $file->getRegion(0)->getWidth());
        $this->assertEquals(3, $file->getRegion(0)->getLines());
        $this->assertEquals('0%,100%', $file->getRegion(0)->getRegionAnchor());
        $this->assertEquals('10%,90%', $file->getRegion(0)->getViewportAnchor());
        $this->assertEquals('up', $file->getRegion(0)->getScroll());

        // cues
        $this->assertEquals(6, $file->getCuesCount());
        $this->assertEquals('00:00:07.500', $file->getCue(3)->getStart());
        $this->assertEquals('00:00:27.500', $file->getCue(3)->getStop());
        $this->assertEquals("<v Bill>Sure! I've only had one today.", $file->getCue(3)->getText());
    }

    public function testIfWeGetTheFirstCue()
    {
        // example file from W3C spec
        $filename = __DIR__.'/../../Fixtures/example-1.vtt';
        $file = new WebvttFile($filename);

        $expectedCue = new WebvttCue('00:00:00.000', '00:00:20.000');
        $expectedCue->addTextLine('<v Fred>Hi, my name is Fred,');
        $expectedCue->addTextLine('nice to meet you.');
        $expectedCue->setSetting('region', 'fred');
        $expectedCue->setSetting('align', 'left');

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }

    public function testIfWeGetTheLastCue()
    {
        // example file from W3C spec
        $filename = __DIR__.'/../../Fixtures/example-1.vtt';
        $file = new WebvttFile($filename);

        $expectedCue = new WebvttCue('00:00:12.500', '00:00:32.500', "<v Fred>OK, let's go.");
        $expectedCue->setSetting('region', 'fred');
        $expectedCue->setSetting('align', 'left');

        $this->assertEquals($expectedCue, $file->getLastCue());
    }
}