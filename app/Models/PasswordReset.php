<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PasswordReset
 * @package App\Models
 *
 * @property string $email
 * @property string $token
 * @property string $created_at
 * @property string $deleted_at
 */
class PasswordReset extends Model
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'password_resets';
}
