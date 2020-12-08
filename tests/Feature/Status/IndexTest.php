<?php

namespace Tests\Feature\Status;

use Tests\TestCase;

class IndexTest extends TestCase
{
    private const URI = 'status';

    public function test_index(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertOk();
    }
}
