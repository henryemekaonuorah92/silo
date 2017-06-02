<?php

namespace Silo\Context;

use Symfony\Component\HttpKernel\Client;

trait ClientContextTrait
{
    protected $client;

    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
