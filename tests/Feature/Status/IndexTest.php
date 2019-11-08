<?php

namespace Tests\Feature\Status;

use Tests\TestCase;

class IndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->uri = '/status';
    }

    public function test_index()
    {
        $response = $this->get($this->uri);
        $response->assertStatus(200);
    }
}
