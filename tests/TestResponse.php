<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestResponse as BaseTestResponse;

class TestResponse extends BaseTestResponse
{
    /**
     * Assert that the response has the given status code
     * and correct error structure
     *
     * @param int $status
     * @param bool $hasDataField
     */
    public function assertError(int $status, bool $hasDataField = false)
    {
        $this->assertStatus($status);

        $structure = ['success', 'message', 'error_type'];
        if ($hasDataField) {
            array_push($structure, 'data');
        }
        $this->assertJsonStructure($structure);
    }
}
