<?php
namespace Tests\Feature\Roles;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'roles/count';

    public function test_count_route_is_not_registered(): void
    {
        $this->assertFalse(Route::has('roles.count'));
    }
}
