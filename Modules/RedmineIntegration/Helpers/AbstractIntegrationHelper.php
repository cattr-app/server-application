<?php

namespace Modules\RedmineIntegration\Helpers;

use Modules\RedmineIntegration\Models\ClientFactory;

abstract class AbstractIntegrationHelper
{
    protected ClientFactory $clientFactory;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
}
