<?php

namespace Captioning;

interface FileInterface
{
    /**
     * @return FileInterface
     */
    public function parse();

    /**
     * @return FileInterface
     */
    public function build();

    /**
     * @param int $_from
     * @param int $_to
     * @return FileInterface
     */
    public function buildPart($_from, $_to);
}
