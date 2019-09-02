<?php

namespace Captioning\Format;

class WebvttFileTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    private $pathToVtts;

    protected function setUp()
    {
        parent::setUp();

        $ds = DIRECTORY_SEPARATOR;
        $this->pathToVtts = __DIR__.$ds.'..'.$ds.'..'.$ds.'Fixtures'.$ds.'Webvtt'.$ds;
    }

    /**
     * @return array
     */
    public function parseProvider()
    {
        return array(
            array('1-non-normative_sample-file-captions.vtt'),
            array('1.1-non-normative_cues-with-multiple-lines.vtt'),
            array('1.2-non-normative_comments_1.vtt'),
            array('1.2-non-normative_comments_2.vtt'),
            array('1.3-non-normative_other-features_1.vtt'),
            array('1.3-non-normative_other-features_2.vtt'),
            array('1.3-non-normative_other-features_3.vtt'),
            array('1.3-non-normative_other-features_4.vtt'),
            array('long-hours.vtt'),
            array('empty.vtt'),
        );
    }

    /**
     * @param string $vtt
     * @dataProvider parseProvider
     */
    public function testParse($vtt)
    {
        $webVttFile = $this->getWebvttFile($this->pathToVtts.$vtt);
        $this->assertSame($webVttFile, $webVttFile->parse());
    }

    /**
     * @return array
     */
    public function parseExceptionProvider()
    {
        return array(
            array(
                'invalid_1-non-normative_sample-file-captions.vtt',
                array(
                    'Invalid file header (must be "WEBVTT" with optionnal description)',
                    'Incorrect Region definition at line 2',
                    'Incorrect Region definition at line 3',
                    'Malformed cue detected at line 7',
                    'Malformed cue detected at line 13',
                    'Malformed cue detected at line 19',
                    'Malformed cue detected at line 33',
                ),
            ),
            array(
                'invalid_1.1-non-normative_cues-with-multiple-lines.vtt',
                array(
                    'Malformed cue detected at line 10',
                    'Malformed cue detected at line 15',
                ),
            ),
            array(
                'invalid_1.2-non-normative_comments_1.vtt',
                array(
                    'Malformed cue detected at line 7',
                ),
            ),
            array(
                'invalid_1.2-non-normative_comments_2.vtt',
                array(
                    'Malformed cue detected at line 9',
                    'Malformed cue detected at line 12',
                    'Malformed cue detected at line 23',
                ),
            ),
            array(
                'invalid_1.3-non-normative_other-features_1.vtt',
                array(
                    'Invalid file header (must be "WEBVTT" with optionnal description)',
                    'Malformed cue detected at line 4',
                    'Malformed cue detected at line 9',
                ),
            ),
            array(
                'invalid_1.3-non-normative_other-features_2.vtt',
                array(
                    'Malformed cue detected at line 5',
                    'Malformed cue detected at line 8',
                    'Malformed cue detected at line 11',
                ),
            ),
            array(
                'invalid_1.3-non-normative_other-features_3.vtt',
                array(
                    'Malformed cue detected at line 5',
                    'Malformed cue detected at line 8',
                ),
            ),
            array(
                'invalid_1.3-non-normative_other-features_4.vtt',
                array(
                    'Incorrect Region definition at line 2',
                    'Unable to parse the string as WebvttRegion'
                ),
            ),
            array(
                'invalid_1.3-non-normative_other-features_5.vtt',
                array(
                    'File description must not contain "-->"'
                ),
            ),
        );
    }

    /**
     * @param string $vtt
     * @param array $errors
     * @dataProvider parseExceptionProvider
     */
    public function testParseException($vtt, array $errors)
    {
        $this->setExpectedException(
            'Exception',
            'The following errors were found while parsing the file:'."\n".implode("\n", $errors)
        );
        $webVttFile = $this->getWebvttFile($this->pathToVtts.$vtt);
        $webVttFile->parse();
    }

    public function testRegionsEmpty()
    {
        $webVttFile = $this->getWebvttFile($this->pathToVtts.'1.3-non-normative_other-features_1.vtt');

        $this->assertNull($webVttFile->getRegion(0));
    }

    public function testRegions()
    {
        $webVttFile = $this->getWebvttFile($this->pathToVtts.'1.3-non-normative_other-features_4.vtt');
        $region = new WebvttRegion('fred', '40%', '3', '0%,100%', '10%,90%', 'up');

        $this->assertEquals($region, $webVttFile->getRegion(0));
    }

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
        $filename = __DIR__ . '/../../Fixtures/Webvtt/example-1.vtt';
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
        $filename = __DIR__ . '/../../Fixtures/Webvtt/example-1.vtt';
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
        $filename = __DIR__ . '/../../Fixtures/Webvtt/example-1.vtt';
        $file = new WebvttFile($filename);

        $expectedCue = new WebvttCue('00:00:12.500', '00:00:32.500', "<v Fred>OK, let's go.");
        $expectedCue->setSetting('region', 'fred');
        $expectedCue->setSetting('align', 'left');

        $this->assertEquals($expectedCue, $file->getLastCue());
    }

    /**
     * @param string $filename
     * @param string $encoding
     * @param boolean $useIconv
     * @return WebvttFile
     */
    private function getWebvttFile($filename, $encoding = null, $useIconv = false)
    {
        return new WebvttFile($filename, $encoding = null, $useIconv = false);
    }
}
