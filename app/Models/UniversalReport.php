<?php

namespace App\Models;

use App\Enums\UniversalReportType;
use App\Enums\UniversalReportBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'base',
        'data_objects',
        'fields',
        'charts',
    ];

    protected $casts = [
        'type' => UniversalReportType::class,
        'base' => UniversalReportBase::class,
        'data_objects' => 'array',
        'fields' => 'array',
        'charts' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Personal reports are visible to their owner only. Company reports are visible to an administrator
     * and to users who have access to every data object of the report.
     */
    public function canBeViewedBy(User $user): bool
    {
        if ($this->type === UniversalReportType::PERSONAL) {
            return $this->isOwnedBy($user);
        }

        return $user->isAdmin() || (bool)$this->base->checkAccess($this->data_objects);
    }

    /**
     * Personal reports are managed by their owner only (administrators included),
     * company reports are managed by administrators only.
     */
    public function canBeManagedBy(User $user): bool
    {
        if ($this->type === UniversalReportType::PERSONAL) {
            return $this->isOwnedBy($user);
        }

        return $user->isAdmin();
    }

    private function isOwnedBy(User $user): bool
    {
        return (int)$this->user_id === (int)$user->id;
    }
}
