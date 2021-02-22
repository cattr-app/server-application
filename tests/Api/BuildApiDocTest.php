<?php


namespace Tests\Api;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class BuildApiDocTest extends TestCase
{
    private const PROCESS_EXIT_STATUS_CODE_OK = 0;

    public function test_api_docs_must_build_succesfully(): void
    {
        $pathToNodeJs = env('PATH_TO_NODEJS');

        if ($pathToNodeJs === null || !file_exists($pathToNodeJs)) {
            $this->markAsRisky();

            return;
        }

        $packageJson = json_decode(file_get_contents('./package.json'), true, 512, JSON_THROW_ON_ERROR);
        $customApiCommand = $packageJson['scripts']['custom-api'];
        $commandBuildApi = str_replace('node ', sprintf("%s ", $pathToNodeJs), $customApiCommand);
        $process = Process::fromShellCommandline($commandBuildApi);
        $exitStatus = $process->run();

        self::assertSame(self::PROCESS_EXIT_STATUS_CODE_OK, $exitStatus);
    }
}
