Captioning [![Build Status](https://secure.travis-ci.org/captioning/captioning.png)](http://travis-ci.org/captioning/captioning) [![Latest Stable Version](https://poser.pugx.org/captioning/captioning/v/stable.svg)](https://packagist.org/packages/captioning/captioning) [![Total Downloads](https://poser.pugx.org/captioning/captioning/downloads.svg)](https://packagist.org/packages/captioning/captioning) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/c6cb7b96-0629-45e5-9f9f-a256a5866cb0/mini.png)](https://insight.sensiolabs.com/projects/c6cb7b96-0629-45e5-9f9f-a256a5866cb0)
==========

Captioning is a collection of tools made to help you create and edit subtitles in different formats:

* Subrip (.srt)
* WebVTT (.vtt)
* Substation Alpha (.ass)
* [TODO] Timed Text (.dfxp)

# Installation

Add the following to your composer.json file:
``` json
"require":
{
    "captioning/captioning": "1.*"
}
```

Then run:

``` sh
composer install
```

# Usage

Examples and snippets in the [wiki](https://github.com/captioning/captioning/wiki).

# TODO-list
* [Substation Alpha] add dynamic parsing if events format is not the default one
* [WebVTT] Check if the region defined in WebvttFile object in WebvttCue::checkSetting()
