<?php

namespace Tests\Feature\Rules;

use Tests\Facades\UserFactory;
use App\Models\Rule;
use App\User;
use Tests\TestCase;

/**
 * Class ActionsTest
 * @package Tests\Feature\Rules
 */
class ActionsTest extends TestCase
{
    private const URI = 'v1/rules/actions';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_actions()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();

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

        //TODO ¯\_(ツ)_/¯
        $response->assertJson($items);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);
        $response->assertUnauthorized();
    }

    public function test_forbidden()
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertForbidden();
    }
}
