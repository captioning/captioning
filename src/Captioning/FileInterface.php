<?php

namespace Captioning;

interface FileInterface
{
    /**
     * @return FileInterface
     */
    public function parse(): self;

    /**
     * @return FileInterface
     */
    public function build(): self;

    /**
     * @param int $_from
     * @param int $_to
     * @return FileInterface
     */
    public function buildPart(int $_from, int $_to): self;
}
