<?php

namespace App\Helpers;

use CzProject\GitPhp\Git;
use Exception;
use Module;
use Throwable;

class Version
{
    private string $version = 'dev';

    public function __construct(protected ?string $module = null)
    {
        $repo = (new Git())->open($module ? Module::findOrFail($module)->getPath() : base_path());

        try {
            $tags = $repo->getTags();

            $lastTag = array_pop($tags);

            $this->version = app()->isLocal() && !str_contains($lastTag, '-') ? "$lastTag-dev" : $lastTag;
        } catch (Throwable) {
            $this->version = env('APP_VERSION', 'dev');
        }
    }

    public function __toString(): string
    {
        return preg_replace('/^v(.*)$/', '$1', $this->version);
    }
}
