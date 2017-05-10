<?php

namespace Captioning\Format;

class SBVFileTest extends \PHPUnit_Framework_TestCase
{
    public function testIfAFileIsParsedProperly()
    {
        $filename = __DIR__.'/../../Fixtures/SBV/example.sbv';
        $file = new SBVFile($filename);

        // cues
        $this->assertEquals(4, $file->getCuesCount());
        $this->assertEquals(346000, $file->getCue(1)->getStartMS());
        $this->assertEquals(351000, $file->getCue(1)->getStopMS());
        $this->assertEquals(5000, $file->getCue(1)->getDuration());
        $this->assertEquals("Because every child in our society is
a part of that society.", $file->getCue(1)->getText());
    }

    public function testIfWeGetTheFirstCue()
    {
        $filename = __DIR__.'/../../Fixtures/SBV/example.sbv';
        $file = new SBVFile($filename);

        $expectedCue = new SBVCue(SBVCue::ms2tc(340000), SBVCue::ms2tc(346000));
        $expectedCue->addTextLine("Don’t think that you can just ignore them");
        $expectedCue->addTextLine("because they’re not your children or relatives.");

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }

    public function testIfWeGetTheLastCue()
    {
        $filename = __DIR__.'/../../Fixtures/SBV/example.sbv';
        $file = new SBVFile($filename);

        $expectedCue = new SBVCue(SBVCue::ms2tc(355000), SBVCue::ms2tc(359000));
        $expectedCue->addTextLine("…so that they can grow up to be");
        $expectedCue->addTextLine("good adults in the future.");

        $this->assertEquals($expectedCue, $file->getLastCue());
    }
}
