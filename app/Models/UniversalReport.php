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
}
