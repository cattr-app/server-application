<?php

namespace App\Models;

use App\Enums\UniversalReport as EnumsUniversalReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'main',
        'data_objects',
        'fields',
        'charts',
    ];

    protected $casts = [
        'data_objects' => 'array',
        'fields' => 'array',
        'charts' => 'array',
        'main' => EnumsUniversalReport::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
