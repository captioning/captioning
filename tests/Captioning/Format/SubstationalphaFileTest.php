<?php

namespace Captioning\Format;

class SubstationaplphaFileTest extends \PHPUnit_Framework_TestCase {

    public function testIfAFileV4IsParsedProperly() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ssa_v4_valid.ssa';
        $file = new SubstationalphaFile($filename);

        // header
        $this->assertEquals('v4.00', $file->getScriptType());
        
        // cues
        $this->assertEquals(6, $file->getCuesCount());
      
        // first cue
        $this->assertEquals(0, $file->getCue(0)->getStartMS());
        $this->assertEquals(20000, $file->getCue(0)->getStopMS());
        $this->assertEquals(20000.0, $file->getCue(0)->getDuration());
        $this->assertEquals("Hi, my name is Fred,\Nnice to meet you.", $file->getCue(0)->getText());

        // second cue
        $this->assertEquals(21500, $file->getCue(1)->getStartMS());
        $this->assertEquals(22500, $file->getCue(1)->getStopMS());
        $this->assertEquals(1000.0, $file->getCue(1)->getDuration());
        $this->assertEquals("Hi, I'm Bill.", $file->getCue(1)->getText());
        
        // third cue
        $this->assertEquals(23000, $file->getCue(2)->getStartMS());
        $this->assertEquals(25000, $file->getCue(2)->getStopMS());
        $this->assertEquals(2000.0, $file->getCue(2)->getDuration());
        $this->assertEquals("Would you like to get a coffee?", $file->getCue(2)->getText());
        
        // fourth cue
        $this->assertEquals(27500, $file->getCue(3)->getStartMS());
        $this->assertEquals(37500, $file->getCue(3)->getStopMS());
        $this->assertEquals(10000.0, $file->getCue(3)->getDuration());
        $this->assertEquals("Sure! I've only had one today.", $file->getCue(3)->getText());
        
        // fifth cue
        $this->assertEquals(40000, $file->getCue(4)->getStartMS());
        $this->assertEquals(41000, $file->getCue(4)->getStopMS());
        $this->assertEquals(1000.0, $file->getCue(4)->getDuration());
        $this->assertEquals("This is my fourth!", $file->getCue(4)->getText());
        
        // fifth cue
        $this->assertEquals(72500, $file->getCue(5)->getStartMS());
        $this->assertEquals(92500, $file->getCue(5)->getStopMS());
        $this->assertEquals(20000.0, $file->getCue(5)->getDuration());
        $this->assertEquals("OK, let's go.", $file->getCue(5)->getText());
    }
    
    public function testIfWeGetTheFirstV4Cue() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ssa_v4_valid.ssa';
        $file = new SubstationalphaFile($filename);

        $expectedCue = new SubstationalphaCue('0:00:00.00', '0:00:20.00', "Hi, my name is Fred,\Nnice to meet you.");

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }
    
    public function testIfWeGetTheLastV4Cue() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ssa_v4_valid.ssa';
        $file = new SubstationalphaFile($filename);

        $expectedCue = new SubstationalphaCue('0:01:12.50', '0:01:32.50', "OK, let's go.");

        $this->assertEquals($expectedCue, $file->getLastCue());
    }
    
        /**
     * @expectedException Exception
     */
    public function testReadInvalidV4File() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ssa_v4_invalid.ssa';
     
        $file = new SubstationalphaFile($filename);
    }
    
    public function testIfAFileV4plusIsParsedProperly() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_valid.ass';
        $file = new SubstationalphaFile($filename);

        // header
        $this->assertEquals('v4.00+', $file->getScriptType());
        
        // cues
        $this->assertEquals(6, $file->getCuesCount());

        // first cue
        $this->assertEquals(0, $file->getCue(0)->getStartMS());
        $this->assertEquals(20000, $file->getCue(0)->getStopMS());
        $this->assertEquals(20000.0, $file->getCue(0)->getDuration());
        $this->assertEquals("Hi, my name is Fred,\Nnice to meet you.", $file->getCue(0)->getText());

        // second cue
        $this->assertEquals(21500, $file->getCue(1)->getStartMS());
        $this->assertEquals(22500, $file->getCue(1)->getStopMS());
        $this->assertEquals(1000.0, $file->getCue(1)->getDuration());
        $this->assertEquals("Hi, I'm Bill.", $file->getCue(1)->getText());
        
        // third cue
        $this->assertEquals(23000, $file->getCue(2)->getStartMS());
        $this->assertEquals(25000, $file->getCue(2)->getStopMS());
        $this->assertEquals(2000.0, $file->getCue(2)->getDuration());
        $this->assertEquals("Would you like to get a coffee?", $file->getCue(2)->getText());
        
        // fourth cue
        $this->assertEquals(27500, $file->getCue(3)->getStartMS());
        $this->assertEquals(37500, $file->getCue(3)->getStopMS());
        $this->assertEquals(10000.0, $file->getCue(3)->getDuration());
        $this->assertEquals("Sure! I've only had one today.", $file->getCue(3)->getText());
        
        // fifth cue
        $this->assertEquals(40000, $file->getCue(4)->getStartMS());
        $this->assertEquals(41000, $file->getCue(4)->getStopMS());
        $this->assertEquals(1000.0, $file->getCue(4)->getDuration());
        $this->assertEquals("This is my fourth!", $file->getCue(4)->getText());
        
        // fifth cue
        $this->assertEquals(72500, $file->getCue(5)->getStartMS());
        $this->assertEquals(92500, $file->getCue(5)->getStopMS());
        $this->assertEquals(20000.0, $file->getCue(5)->getDuration());
        $this->assertEquals("OK, let's go.", $file->getCue(5)->getText());
    }

    public function testIfWeGetTheFirstV4plusCue() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_valid.ass';
        $file = new SubstationalphaFile($filename);

        $expectedCue = new SubstationalphaCue('0:00:00.00', '0:00:20.00', "Hi, my name is Fred,\Nnice to meet you.");

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }

    public function testIfWeGetTheLastV4plusCue() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_valid.ass';
        $file = new SubstationalphaFile($filename);

        $expectedCue = new SubstationalphaCue('0:01:12.50', '0:01:32.50', "OK, let's go.");

        $this->assertEquals($expectedCue, $file->getLastCue());
    }
    
    /**
     * @expectedException Exception
     */
    public function testReadInvalidV4plusFile() {
        $filename = __DIR__ . '/../../Fixtures/Substationalpha/ass_v4plus_invalid.ass';
     
        $file = new SubstationalphaFile($filename);
    }

}
