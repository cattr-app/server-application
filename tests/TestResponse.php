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
    public function assertError(int $status, string $type = null, bool $hasInfo = false): TestResponse
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
     * @return TestResponse
     */
    public function assertUnauthorized(string $type = 'authorization.unauthorized', bool $hasInfo = false): TestResponse
    {
        return $this->assertError(self::HTTP_UNAUTHORIZED, $type, $hasInfo);
    }

    /**
     * @param string $type
     * @param bool $hasInfo
     * @return TestResponse
     */
    public function assertForbidden(string $type = 'authorization.forbidden', bool $hasInfo = true): TestResponse
    {
        return $this->assertError(self::HTTP_FORBIDDEN, $type, $hasInfo);
    }

    /**
     * @param string $type
     * @param bool $hasInfo
     * @return TestResponse
     */
    public function assertValidationError(string $type = 'validation', bool $hasInfo = true): TestResponse
    {
        return $this->assertError(self::HTTP_BAD_REQUEST, $type, $hasInfo);
    }

    /**
     * @param string $type
     * @param bool $hasInfo
     * @return TestResponse
     */
    public function assertItemNotFound(string $type = 'query.item_not_found', bool $hasInfo = false): TestResponse
    {
        return $this->assertError(self::HTTP_NOT_FOUND, $type, $hasInfo);
    }

    /**
     * Assert that the response has the given status code
     * and correct structure
     *
     * @param int $status
     * @return TestResponse
     */
    public function assertSuccess(int $status = self::HTTP_OK): TestResponse
    {
        $this->assertStatus($status);
        $this->assertJson(['success' => true]);

        return $this;
    }
}
