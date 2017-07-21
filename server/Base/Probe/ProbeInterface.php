<?php

namespace Silo\Base\Probe;

interface ProbeInterface
{
    /**
     * @param ProbingWindow|null $window
     * @return mixed
     */
    public function probe(ProbingWindow $window = null);
}