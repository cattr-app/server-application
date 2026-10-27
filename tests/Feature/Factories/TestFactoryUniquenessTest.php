<?php

namespace Tests\Feature\Factories;

use Tests\Facades\InvitationFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * The test factories in tests/Factories used to create a new Faker generator on every call, so
 * `unique()` only applied within one call and users created in the same test could get the same
 * e-mail (an intermittent unique-key violation, e.g. in ProjectMembers\ShowTest). They now share
 * the application's generator, which is reset together with the application for every test.
 */
class TestFactoryUniquenessTest extends TestCase
{
    // Large enough that independent generators almost surely repeat a safeEmail, small enough to stay fast.
    private const SAMPLES = 1000;

    public function test_user_emails_are_unique_across_factory_calls(): void
    {
        $emails = [];
        for ($i = 0; $i < self::SAMPLES; $i++) {
            $emails[] = UserFactory::createRandomModelData()['email'];
            $emails[] = UserFactory::createRandomRegistrationModelData()['email'];
        }

        $this->assertSameSize($emails, array_unique($emails));
    }

    public function test_invitation_emails_are_unique_across_factory_calls(): void
    {
        $emails = [];
        for ($i = 0; $i < self::SAMPLES; $i++) {
            $emails[] = InvitationFactory::createRandomModelData()['email'];
        }

        $this->assertSameSize($emails, array_unique($emails));
    }
}
