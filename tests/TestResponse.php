<?php

namespace Tests;

use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Foundation\Testing\TestResponse as BaseTestResponse;

/**
 * Class TestResponse
 * @package Tests
 */
class TestResponse extends BaseTestResponse
{
    /**
     * Assert that the response has the given status code
     * and correct error structure
     *
     * @param int $status
     * @param bool $hasInfo
     */
    public function assertApiError(int $status, bool $hasInfo = false)
    {
        $this->assertStatus($status);
        $this->assertJson(['success' => false]);

        $structure = ['success', 'message', 'error_type'];
        if ($hasInfo) {
            $structure[] = 'info';
        } else {
            PHPUnit::assertArrayNotHasKey('info', $this->decodeResponseJson());
        }

        $this->assertJsonStructure($structure);
    }

    /**
     * Assert that the response has the given status code
     * and correct structure
     *
     * @param int $status
     */
    public function assertApiSuccess(int $status = 200)
    {
        $this->assertStatus($status);
        $this->assertJson(['success' => true]);
    }
}
