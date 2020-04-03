<?php

namespace Modules\RedmineIntegration\Console;

use Exception;
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
     * @throws Exception
     */
    public function handle(): void
    {
        $key = $this->generateRandomKey();

        $this->setKeyInEnvironmentFile($key);

        $this->laravel['config']['redmineintegration.request.signature'] = $key;

        $this->info("Redmine signature key [$key] set successfully.");
    }

    /**
     * Generate a random key for the application.
     *
     * @throws Exception
     */
    protected function generateRandomKey(): string
    {
        return base64_encode(random_bytes(64));
    }

    /**
     * Set the application key in the environment file.
     */
    protected function setKeyInEnvironmentFile(string $key): bool
    {
        $this->writeNewEnvironmentFileWith($key);
        return true;
    }

    /**
     * Write a new environment file with the given key.
     */
    protected function writeNewEnvironmentFileWith(string $key): void
    {
        file_put_contents($this->getEnvFilePath(), preg_replace(
            $this->keyReplacementPattern(),
            'REQUEST_SIGNATURE=' . $key,
            file_get_contents($this->getEnvFilePath())
        ));
    }

    /**
     * @return mixed
     */
    protected function getEnvFilePath()
    {
        return $this->laravel->environmentFilePath();
    }

    /**
     * Get a regex pattern that will match env REQUEST_SIGNATURE with any random key.
     */
    protected function keyReplacementPattern(): string
    {
        $escaped = preg_quote('=' . $this->laravel['config']['redmineintegration.request.signature'], '/');

        return "/^REQUEST_SIGNATURE{$escaped}/m";
    }
}
