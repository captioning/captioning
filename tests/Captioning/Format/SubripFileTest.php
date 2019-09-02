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
        $filename = __DIR__ . '/../../Fixtures/Subrip/example-1.srt';
        $file = new SubripFile($filename);

        // cues
        $this->assertEquals(6, $file->getCuesCount());
        $this->assertEquals('00:00:27,500', $file->getCue(3)->getStart());
        $this->assertEquals('00:00:37,500', $file->getCue(3)->getStop());
        $this->assertEquals("Sure! I've only had one today.", $file->getCue(3)->getText());
    }

    public function testIfANonUTF8EncodedFileIsParsedProperly()
    {
        $filename = __DIR__ . '/../../Fixtures/Subrip/non-utf8-win-eol.srt';
        $file = new SubripFile($filename, 'ISO-8859-2');

        // cues
        $this->assertEquals(8, $file->getCuesCount());
        $this->assertEquals('00:00:00,340', $file->getCue(0)->getStart());
        $this->assertEquals('00:00:03,860', $file->getCue(0)->getStop());
        $this->assertEquals("<i>Az előző részek tartalmából...</i>", $file->getCue(0)->getText());

        $this->assertEquals('00:00:07,010', $file->getCue(2)->getStart());
        $this->assertEquals('00:00:10,150', $file->getCue(2)->getStop());
        $this->assertEquals("Kocsiba ülök, és mellette megyek majd.\r\nNem mehet egyedül.", $file->getCue(2)->getText());
    }

    public function testIfAFileWithoutTrailingNewlineIsParsedProperly()
    {
        $filename = __DIR__ . '/../../Fixtures/Subrip/example-nonewline.srt';
        $file = new SubripFile($filename);

        $this->assertEquals(3, $file->getCuesCount());
        $this->assertEquals('Would you like to get a coffee?', $file->getCue(2)->getText());
    }

    public function testIfWeGetTheFirstCue()
    {
        // example file from W3C spec
        $filename = __DIR__ . '/../../Fixtures/Subrip/example-1.srt';
        $file = new SubripFile($filename);
        $file->setLineEnding(SubripFile::UNIX_LINE_ENDING);

        $expectedCue = new SubripCue('00:00:00,000', '00:00:20,000');
        $expectedCue->addTextLine('Hi, my name is Fred,');
        $expectedCue->addTextLine('nice to meet you.');

        $this->assertEquals($expectedCue, $file->getFirstCue());
    }

    public function testIfWeGetTheLastCue()
    {
        // example file from W3C spec
        $filename = __DIR__ . '/../../Fixtures/Subrip/example-1.srt';
        $file = new SubripFile($filename);
        $file->setLineEnding(SubripFile::UNIX_LINE_ENDING);

        $expectedCue = new SubripCue('00:01:12,500', '00:01:32,500', "OK, let's go.");

        $this->assertEquals($expectedCue, $file->getLastCue());
    }

    public function testSameEndTimeInPrevAndStartTimeInNext()
    {
        $filename = __DIR__ . '/../../Fixtures/Subrip/passed-with-same-end-in-prev-and-start-next-cue.srt';
        $file = new SubripFile($filename);
        $this->assertInstanceOf('Captioning\Format\SubripFile', $file);
    }

    public function testDoesNotAllowSameStartAndEndTime()
    {
        $filename = __DIR__ . '/../../Fixtures/Subrip/failed-equal-start-and-end-time-in-last-queue.srt';
        $this->setExpectedException('\Exception', $filename.' is not a proper .srt file.');
        new SubripFile($filename);
    }


    public function testDoesNotAllowSameOrderIndex()
    {
        $filename = __DIR__ . '/../../Fixtures/Subrip/failed-equal-subtitle-order-number.srt';
        $this->setExpectedException('\Exception', $filename.' is not a proper .srt file.');
        new SubripFile($filename);
    }
}
