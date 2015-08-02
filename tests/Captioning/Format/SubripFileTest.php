<?php

namespace Captioning\Format;

class SubripFileTest extends \PHPUnit_Framework_TestCase
{
    public function testAddingCuesBothWays()
    {
        $start = '01:02:03.456';
        $stop = '01:02:04.567';
        $text = 'Once upon a time';
        $cue = new SubripCue($start, $stop, $text);

        $file = new SubripFile();
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
        $filename = __DIR__.'/../../Fixtures/example-1.srt';
        $file = new SubripFile($filename);

        // cues
        $this->assertEquals(6, $file->getCuesCount());
        $this->assertEquals('00:00:27,500', $file->getCue(3)->getStart());
        $this->assertEquals('00:00:37,500', $file->getCue(3)->getStop());
        $this->assertEquals("Sure! I've only had one today.", $file->getCue(3)->getText());

    }

    public function testIfWeGetTheFirstCue()
    {
        // example file from W3C spec
        $filename = __DIR__.'/../../Fixtures/example-1.srt';
        $file = new SubripFile($filename);

        $expectedCue = new SubripCue('00:00:00,000', '00:00:20,000');
        $expectedCue->addTextLine('Hi, my name is Fred,');
        $expectedCue->addTextLine('nice to meet you.');

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }

    public function testIfWeGetTheLastCue()
    {
        // example file from W3C spec
        $filename = __DIR__.'/../../Fixtures/example-1.srt';
        $file = new SubripFile($filename);

        $expectedCue = new SubripCue('00:01:12,500', '00:01:32,500', "OK, let's go.");

        $this->assertEquals($expectedCue, $file->getLastCue());
    }
}
