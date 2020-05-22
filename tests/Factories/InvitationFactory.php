<?php

namespace Tests\Factories;

use App\Models\Invitation;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Model;

class InvitationFactory extends Factory
{
    private Invitation $invitation;

    protected function getModelInstance(): Model
    {
        return $this->invitation;
    }

    public function createRequestData(): array
    {
        $faker = FakerFactory::create();

        return [
            'users' => [
                [
                    'email' => $faker->unique()->email,
                    'role_id' => 1
                ]
            ],
        ];
    }

    public function createRandomModelData(): array
    {
        $faker = FakerFactory::create();

        return [
            'email' => $faker->unique()->email,
            'key' => $faker->uuid,
            'expires_at' => now()->addDays(1),
        ];
    }

    public function create(): Model
    {
        $modelData = $this->createRandomModelData();

        $this->invitation = Invitation::make($modelData);

        $this->invitation->save();

        return $this->invitation;
    }
}
