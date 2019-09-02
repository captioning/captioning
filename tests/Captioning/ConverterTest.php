<?php

namespace Captioning;

use Captioning\Format\SubripFile;
use Captioning\Format\WebvttFile;
use Captioning\Format\SubstationalphaFile;

class ConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testSubrip2WebvttConversion()
    {
        // example file from W3C spec
        $filename = __DIR__ . '/../Fixtures/Subrip/example-1.srt';
        $file = new SubripFile($filename);

        $content = "WEBVTT

00:00:00.000 --> 00:00:20.000
Hi, my name is Fred,
nice to meet you.

00:00:21.500 --> 00:00:22.500
Hi, I'm Bill.

00:00:23.000 --> 00:00:25.000
Would you like to get a coffee?

00:00:27.500 --> 00:00:37.500
Sure! I've only had one today.

00:00:40.000 --> 00:00:41.000
This is my fourth!

00:01:12.500 --> 00:01:32.500
OK, let's go.

";

            $this->assertEquals($content, $file->convertTo('webvtt')->build()->getFileContent());
    }

    public function testWebvtt2SubripConversion()
    {
        // example file from W3C spec
        $filename = __DIR__ . '/../Fixtures/Webvtt/example-1.vtt';
        $file = new WebvttFile($filename);

        $content = "1
00:00:00,000 --> 00:00:20,000
<v Fred>Hi, my name is Fred,
nice to meet you.

2
00:00:02,500 --> 00:00:22,500
<v Bill>Hi, I'm Bill.

3
00:00:05,000 --> 00:00:25,000
<v Fred>Would you like to get a coffee?

4
00:00:07,500 --> 00:00:27,500
<v Bill>Sure! I've only had one today.

5
00:00:10,000 --> 00:00:30,000
<v Fred>This is my fourth!

6
00:00:12,500 --> 00:00:32,500
<v Fred>OK, let's go.

";

        $this->assertEquals($content, $file->convertTo('subrip')->build()->getFileContent());
    }

    public function testSubrip2SubstationalphaConversion()
    {
        // example file from W3C spec
        $filename = __DIR__ . '/../Fixtures/Subrip/example-1.srt';
        $file = new SubripFile($filename);

        $content = "[Script Info]
Title: <untitled>
Original Script: <unknown>
ScriptType: v4.00+
Collisions: Normal
PlayResX: 384
PlayResY: 288
PlayDepth: 0
Timer: 100.0
WrapStyle: 0

[v4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Default, Arial, 20, &H00FFFFFF, &H00000000, &H00000000, &H00000000, 0, 0, 0, 0, 100, 100, 0, 0, 1, 2, 0, 2, 15, 15, 15, 0

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
Dialogue: 0,0:00:00.00,0:00:20.00,Default,,0000,0000,0000,,Hi, my name is Fred,\Nnice to meet you.
Dialogue: 0,0:00:21.50,0:00:22.50,Default,,0000,0000,0000,,Hi, I'm Bill.
Dialogue: 0,0:00:23.00,0:00:25.00,Default,,0000,0000,0000,,Would you like to get a coffee?
Dialogue: 0,0:00:27.50,0:00:37.50,Default,,0000,0000,0000,,Sure! I've only had one today.
Dialogue: 0,0:00:40.00,0:00:41.00,Default,,0000,0000,0000,,This is my fourth!
Dialogue: 0,0:01:12.50,0:01:32.50,Default,,0000,0000,0000,,OK, let's go.
";

        $this->assertEquals($content, $file->convertTo('substationalpha')->build()->getFileContent());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidConverterException()
    {
        $file = new SubripFile();

        $file->convertTo('foor');
    }
}
