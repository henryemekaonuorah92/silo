<?php

namespace Silo\Context;

use Symfony\Component\HttpKernel\Client;

/**
 * Uses a Symfony Client to request the app's API.
 */
interface ClientContextInterface
{
    public function setClient(Client $client);
}
