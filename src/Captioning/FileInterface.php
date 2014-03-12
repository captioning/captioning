<?php

namespace Captioning;

interface FileInterface
{
    public function parse();

    public function build();

    public function buildPart($_from, $_to);
}
