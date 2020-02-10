<?php

namespace Modules\RedmineIntegration\Helpers;

use Modules\RedmineIntegration\Models\ClientFactory;

/**
 * Class AbstractIntegrationHelper
 *
 * Abstract integration helper class
*/
abstract class AbstractIntegrationHelper
{
    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
}
