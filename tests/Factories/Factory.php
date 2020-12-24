<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class Factory
{
    protected bool $timestampsHidden = true;
    protected bool $randomRelations = false;

    abstract protected function getModelInstance(): Model;

    abstract public function createRandomModelData(): array;

    abstract public function create(): Model;

    public function createMany(int $amount = 1): Collection
    {
        $models = array_map(fn () => $this->create(), range(0, $amount));
        return collect($models);
    }

    protected function hideTimestamps(): void
    {
        $this->getModelInstance()->makeHidden(['created_at', 'updated_at', 'deleted_at']);
    }

    protected function hideCanAttribute(): void
    {
        $this->getModelInstance()->makeHidden(['can']);
    }
}
