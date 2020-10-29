<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Setting
 *
 * @property int $id
 * @property string $module_name
 * @property string $key
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereModuleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereValue($value)
 * @mixin \Eloquent
 */
class Setting extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'module_name',
        'key',
        'value'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'value' => 'string',
        'work_time' => 'int',
        'language' => 'string',
        'timezone' => 'timezone',
        'color' => 'json',
    ];

    /**
     * Casts the values of the value column by key.
     *
     * @param string $key
     * @return mixed|string
     */
    protected function getCastType($key)
    {
        if ($key == 'value' && !empty($this->casts[$this->key])) {
            return $this->casts[$this->key];
        }

        return parent::getCastType($key);
    }
}
