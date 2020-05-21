<?php

namespace Tests\Factories;

use App\Models\ProjectsUsers;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;

class ProjectUserFactory extends Factory
{
    public const USER_ROLE = 2;
    public const MANAGER_ROLE = 1;
    public const AUDITOR_ROLE = 3;

    private ProjectsUsers $projectUser;
    private ?User $user = null;
    private ?int $roleId = null;

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
            'role_id' =>$this->getRandomRoleId()
        ];
    }

    private function defineModelData(): array
    {
        $this->user ??= UserFactory::create();
        $this->roleId ??= $this->getRandomRoleId();

        return [
            'project_id' => ProjectFactory::create()->id,
            'user_id' => $this->user->id,
            'role_id' => $this->roleId
        ];
    }

    private function getRandomRoleId()
    {
        return Arr::random([self::USER_ROLE, self::MANAGER_ROLE, self::AUDITOR_ROLE]);
    }

    public function forUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function setRole(int $roleId): self
    {
        $this->roleId = $roleId;
        return $this;
    }

    public function create(): ProjectsUsers
    {
        $this->projectUser = ProjectsUsers::create($this->defineModelData());

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        return $this->projectUser;
    }
}
