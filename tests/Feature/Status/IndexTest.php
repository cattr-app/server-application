<?php

namespace Tests\Feature\Status;

use Tests\TestCase;

/**
 * Class IndexTest
 * @package Tests\Feature\Status
 */
class IndexTest extends TestCase
{
    private const URI = 'status';

    public function test_index()
    {
        $response = $this->getJson(self::URI);

        $response->assertSuccess();
    }
}
