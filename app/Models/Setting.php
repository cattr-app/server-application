<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
