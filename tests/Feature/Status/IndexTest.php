<?php

namespace Tests\Feature\Status;

use Tests\TestCase;

/**
 * Class IndexTest
 * @package Tests\Feature\Status
 */
class IndexTest extends TestCase
{
    const URI = 'status';

    public function test_index()
    {
        $response = $this->get(self::URI);
        $response->assertApiSuccess();
    }
}
