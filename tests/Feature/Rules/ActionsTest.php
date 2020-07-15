<?php

namespace Tests\Feature\Rules;

use App\Models\Rule;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ActionsTest extends TestCase
{
    private const URI = 'rules/actions';

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_actions(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertSuccess();

        $items = [];
        foreach (Rule::getActionList() as $object => $actions) {
            foreach ($actions as $action => $name) {
                $items[] = [
                    'object' => $object,
                    'action' => $action,
                    'name' => $name,
                ];
            }
        }

        $response->assertJson(['res' => $items]);

        //TODO change later
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_forbidden(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertForbidden();
    }
}
