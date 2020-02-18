<?php

namespace Tests\Factories;

use App\Models\ProjectsUsers;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;

class ProjectUserFactory extends Factory
{
    private const DEFAULT_ROLE_ID = 1;

    private ProjectsUsers $projectUser;

    protected function getModelInstance(): ProjectsUsers
    {
        return $this->projectUser;
    }

    public function createRandomModelData(): array
    {
        return $this->createModelDataWithRelations();
    }

    public function createModelDataWithRelations(): array
    {
        return [
            'project_id' => ProjectFactory::create()->id,
            'user_id' => UserFactory::create()->id,
            'role_id' => self::DEFAULT_ROLE_ID
        ];
    }

    public function create(array $attributes = []): ProjectsUsers
    {
        $modelData = $this->createRandomModelData();

        $this->projectUser = ProjectsUsers::create($modelData);

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        return $this->projectUser;
    }
}
