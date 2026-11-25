<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;

/** Same isolated application as OIDC feature tests, with process-shared storage. */
class OidcSharedWebTestCase extends OidcWebTestCase
{
    public string $sharedDirectory;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $app['db']->purge('sqlite');
        $app['config']->set([
            'database.connections.sqlite.database' => $this->sharedDirectory . '/database.sqlite',
            'session.driver' => 'file',
            'session.files' => $this->sharedDirectory . '/sessions',
            'session.block_store' => 'oidc-shared-locks',
            'cache.stores.octane' => ['driver' => 'file', 'path' => $this->sharedDirectory . '/grants'],
            'cache.stores.oidc-shared-locks' => ['driver' => 'file', 'path' => $this->sharedDirectory . '/locks'],
        ]);
        $app['cache']->forgetDriver(['octane', 'oidc-shared-locks']);
        return $app;
    }
}
