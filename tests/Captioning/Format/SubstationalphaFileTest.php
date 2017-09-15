<?php

namespace Captioning\Format;

class SubstationaplphaFileTest extends \PHPUnit_Framework_TestCase {

    public function testIfAFileIsParsedProperly() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_valid.ass';
        $file = new SubstationalphaFile($filename);

        // header
        $this->assertEquals('v4.00+', $file->getHeader('ScriptType'));
        
        // cues
        $this->assertEquals(2, $file->getCuesCount());

        // first cue
        $this->assertEquals(101700, $file->getCue(0)->getStartMS());
        $this->assertEquals(106840, $file->getCue(0)->getStopMS());
        $this->assertEquals(5140.0, $file->getCue(0)->getDuration());
        $this->assertEquals("Le rugissement des larmes !\NTu es mon ami.", $file->getCue(0)->getText());

        // second cue
        $this->assertEquals(120990, $file->getCue(1)->getStartMS());
        $this->assertEquals(122870, $file->getCue(1)->getStopMS());
        $this->assertEquals(1880.0, $file->getCue(1)->getDuration());
        $this->assertEquals("Est-ce vraiment Naruto ?", $file->getCue(1)->getText());
    }

    public function testIfWeGetTheFirstCue() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_valid.ass';
        $file = new SubstationalphaFile($filename);

        $expectedCue = new SubstationalphaCue('0:01:41.70', '0:01:46.84', "Le rugissement des larmes !\NTu es mon ami.");

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }

    public function testIfWeGetTheLastCue() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_valid.ass';
        $file = new SubstationalphaFile($filename);

        $expectedCue = new SubstationalphaCue('0:02:00.99', '0:02:02.87', "Est-ce vraiment Naruto ?");

        $this->assertEquals($expectedCue, $file->getLastCue());
    }
    
    /**
     * @expectedException Exception
     */
    public function testReadInvalidFile() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_invalid.ass';
     
        $file = new SubstationalphaFile($filename);
    }

}
