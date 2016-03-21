<?php

namespace Captioning\Format;

class JsonFileTest extends \PHPUnit_Framework_TestCase
{
    public function testIfAFileIsParsedProperly()
    {
        $filename = __DIR__.'/../../Fixtures/Json/en.json';
        $file = new JsonFile($filename);

        // cues
        $this->assertEquals(523, $file->getCuesCount());
        $this->assertEquals(7000, $file->getCue(2)->getStart());
        $this->assertEquals(8000, $file->getCue(2)->getStop());
        $this->assertEquals(1000, $file->getCue(2)->getDuration());
        $this->assertEquals("And the answer I like to offer is", $file->getCue(2)->getText());
    }

    public function testIfWeGetTheFirstCue()
    {
        $filename = __DIR__.'/../../Fixtures/Json/en.json';
        $file = new JsonFile($filename);

        $expectedCue = new JsonCue(1000, 3000);
        $expectedCue->addTextLine("I get asked a lot what the difference between my work is");
        $expectedCue->setStartOfParagraph(true);

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }

    public function testIfWeGetTheLastCue()
    {
        $filename = __DIR__.'/../../Fixtures/Json/en.json';
        $file = new JsonFile($filename);

        $expectedCue = new JsonCue(1396000, 1397000, "Thanks.");
        $expectedCue->setStartOfParagraph(true);

        $this->assertEquals($expectedCue, $file->getLastCue());
    }
}
