<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;

interface GateInterface
{
    public function analyze(string $ticker, DataProviderInterface $provider): GateResult;
}
