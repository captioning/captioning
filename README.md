Captioning [![Build Status](https://secure.travis-ci.org/captioning/captioning.png)](http://travis-ci.org/captioning/captioning) [![Latest Stable Version](https://poser.pugx.org/captioning/captioning/v/stable.svg)](https://packagist.org/packages/captioning/captioning) [![Total Downloads](https://poser.pugx.org/captioning/captioning/downloads.svg)](https://packagist.org/packages/captioning/captioning) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/captioning/captioning/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/captioning/captioning/?branch=master)
==========

Captioning is a collection of tools made to help you create and edit subtitles in different formats:

* Subrip (.srt)
* WebVTT (.vtt)
* Substation Alpha (.ass)
* Youtube Subtitles (.sbv)
* JSON (TED.com) Subtitles (.json)
* [WIP] TTML (.dfxp)

# Installation

``` sh
composer require captioning/captioning "2.*"
```

# Usage

Examples and snippets in the [wiki](https://github.com/captioning/captioning/wiki).

# TODO-list
* [Substation Alpha] add dynamic parsing if events format is not the default one
* [WebVTT] Check if the region defined in WebvttFile object in WebvttCue::checkSetting()
* [TTML] Implement the buildPart method