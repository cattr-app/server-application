<?php

namespace Tests\Factories;

use App\Models\ProjectsUsers;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;

/**
 * Class ProjectUserFactory
 */
class ProjectUserFactory extends AbstractFactory
{
    private const DEFAULT_ROLE_ID = 2;

    /**
     * @return array
     */
    public function generateProjectUserData(): array
    {
        return [
            'project_id' => ProjectFactory::create()->id,
            'user_id' => UserFactory::create()->id,
            'role_id' => self::DEFAULT_ROLE_ID
        ];
    }

    public function create(array $attributes = []): ProjectsUsers
    {
        $projectUserData = $this->generateProjectUserData();

        if ($attributes) {
            $projectUserData = array_merge($projectUserData, $attributes);
        }

        return ProjectsUsers::create($projectUserData);
    }
}
