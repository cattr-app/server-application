<?php

namespace Tests;

use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Foundation\Testing\TestResponse as BaseTestResponse;

class TestResponse extends BaseTestResponse
{
    /**
     * Assert that the response has the given status code
     * and correct error structure
     *
     * @param int $status
     * @param bool $hasInfo
     */
    public function assertError(int $status, bool $hasInfo = false)
    {
        $this->assertStatus($status);

        $structure = ['success', 'message', 'error_type'];
        if ($hasInfo) {
            $structure[] = 'info';
        }
        else {
            PHPUnit::assertArrayNotHasKey('info', $this->decodeResponseJson());
        }

        $this->assertJsonStructure($structure);
    }
}
