<?php

namespace Tests\Feature\Status;

use Tests\TestCase;

/**
 * Class IndexTest
 */
class IndexTest extends TestCase
{
    private const URI = 'status';

    public function test_index(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertSuccess();
    }
}
