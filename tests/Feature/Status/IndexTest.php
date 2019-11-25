<?php

namespace Tests\Feature\Status;

use Tests\TestCase;

class IndexTest extends TestCase
{
    const URI = 'status';

    public function test_index()
    {
        $response = $this->get(self::URI);
        $response->assertStatus(200);
    }
}
