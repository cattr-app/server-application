<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class ActionException extends HttpException
{
    public const ERROR_TYPE_INVALID_ACTION = 'action.invalid_action';

    public const ERROR_TYPE_INTERVAL_ALREADY_DELETED = 'action.interval_already_deleted';

    protected const ERRORS = [
        self::ERROR_TYPE_INVALID_ACTION => ['code' => 409, 'message' => 'Invalid action'],
        self::ERROR_TYPE_INTERVAL_ALREADY_DELETED => ['code' => 409, 'message' => 'Interval already deleted'],
    ];

    public function __construct($type = self::ERROR_TYPE_INVALID_ACTION)
    {
        $this->errorCode = $type;
        $this->status = self::ERRORS[$type]['code'];

        parent::__construct(self::ERRORS[$type]['message']);
    }
}
