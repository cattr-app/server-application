<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;

class GenerateSignature extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine:plugin:signature-generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate HTTP request signature for Redmine Plugin.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        $this->setKeyInEnvironmentFile($key);

        $this->laravel['config']['redmineintegration.request.signature'] = $key;

        $this->info("Redmine signature key [$key] set successfully.");
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     * @throws \Exception
     */
    protected function generateRandomKey()
    {
        return base64_encode(random_bytes(64));
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     *
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $this->writeNewEnvironmentFileWith($key);
        return true;
    }

    /**
     * @return string
     */
    protected function getEnvFilePath()
    {
        return $this->laravel->environmentFilePath();
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     *
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        file_put_contents($this->getEnvFilePath(), preg_replace(
            $this->keyReplacementPattern(),
            'REQUEST_SIGNATURE='.$key,
            file_get_contents($this->getEnvFilePath())
        ));
    }

    /**
     * Get a regex pattern that will match env REQUEST_SIGNATURE with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.$this->laravel['config']['redmineintegration.request.signature'], '/');

        return "/^REQUEST_SIGNATURE{$escaped}/m";
    }
}
