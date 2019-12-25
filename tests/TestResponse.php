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
     * @param string|null $type
     * @param bool $hasInfo
     * @return TestResponse
     */
    public function assertError(int $status, string $type = null, bool $hasInfo = false)
    {
        $this->assertStatus($status);
        $this->assertJson(['success' => false]);


        if ($type) {
            $this->assertJson(['error_type' => $type]);
        }

        $structure = ['success', 'message', 'error_type'];
        if ($hasInfo) {
            $structure[] = 'info';
        } else {
            PHPUnit::assertArrayNotHasKey('info', $this->decodeResponseJson());
        }

        $this->assertJsonStructure($structure);

        return $this;
    }

    /**
     * @param string $type
     * @param bool $hasInfo
     * @return BaseTestResponse|TestResponse
     */
    public function assertUnauthorized(string $type = 'authorization.unauthorized', bool $hasInfo = false)
    {
        return $this->assertError(401, $type, $hasInfo);
    }

    public function assertForbidden(string $type = 'authorization.forbidden', bool $hasInfo = true)
    {
        return $this->assertError(403, $type, $hasInfo);
    }

    public function assertValidationError(string $type = 'validation', bool $hasInfo = true)
    {
        return $this->assertError(400, $type, $hasInfo);
    }

    public function assertItemNotFound(string $type = 'query.item_not_found', bool $hasInfo = false)
    {
        return $this->assertError(404, $type, $hasInfo);
    }

    /**
     * Assert that the response has the given status code
     * and correct structure
     *
     * @param int $status
     * @return TestResponse
     */
    public function assertSuccess(int $status = 200)
    {
        $this->assertStatus($status);
        $this->assertJson(['success' => true]);

        return $this;
    }
}
