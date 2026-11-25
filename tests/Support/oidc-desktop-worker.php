<?php

use App\Exceptions\Entities\AuthorizationException;
use App\Http\Controllers\AuthController;
use Illuminate\Cache\FileLock;
use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Tests\Support\OidcSharedWebTestCase;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

[$script, $directory, $role] = $argv;
$fixture = new OidcSharedWebTestCase('worker');
$fixture->sharedDirectory = $directory;
$app = $fixture->createApplication();

// Observe the real FileLock result. The first holder waits before consuming the
// grant, so the second controller request must reach actual lock contention.
$store = new class(new Filesystem(), $directory . '/locks') extends FileStore {
    public string $signals;
    public string $role;

    public function lock($name, $seconds = 0, $owner = null)
    {
        return new class($this, $name, $seconds, $owner) extends FileLock {
            public function acquire()
            {
                $acquired = parent::acquire();
                $directory = $this->store->signals;
                $role = $this->store->role;
                if (!$acquired) {
                    touch($directory . '/' . $role . '.contended');
                } elseif ($role === 'first') {
                    touch($directory . '/first.acquired');
                    $deadline = microtime(true) + 15;
                    while (!is_file($directory . '/release')) {
                        if (microtime(true) >= $deadline) {
                            $this->release();
                            throw new RuntimeException('Timed out waiting for the parent barrier');
                        }
                        usleep(10000);
                        clearstatcache();
                    }
                }
                return $acquired;
            }
        };
    }
};
$store->signals = $directory;
$store->role = $role;
$app['cache']->store('oidc-shared-locks')->setStore($store);
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$request = Request::create('https://cattr.example.test/api/auth/desktop-key', 'PUT');
$request->headers->set('Authorization', 'desktop ' . $input['key']);

try {
    $data = $app->make(AuthController::class)->authDesktopKey($request)->getData(true)['data'];
    // Do not write the newly issued bearer token to a process log.
    echo json_encode(['outcome' => 'issued', 'user_id' => $data['user']['id']], JSON_THROW_ON_ERROR);
} catch (AuthorizationException $exception) {
    echo json_encode(['outcome' => 'rejected', 'exception' => get_class($exception)], JSON_THROW_ON_ERROR);
}
