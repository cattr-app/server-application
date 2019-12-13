<?php

namespace Tests\Feature\Rules;

use Tests\Factories\UserFactory;
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

        $this->user = app(UserFactory::class)
            ->withTokens()
            ->asUser()
            ->create();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();
    }

    public function test_actions()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOK();

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
        $response->assertApiError(401);
    }

    public function test_forbidden()
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertApiError(403, True);
    }
}
