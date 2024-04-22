<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class TaskRelationException extends HttpException
{
    public const NOT_SAME_PROJECT = 'task_relation.not_same_project';
    public const CYCLIC = 'task_relation.cyclic';
    public const ALREADY_EXISTS = 'task_relation.already_exists';
    public const CANNOT_START_BEFORE_PARENT_ENDS = 'task_relation.cannot_start_before_parent_ends';


    public function __construct($type)
    {
        $ERRORS = [
            self::NOT_SAME_PROJECT => [
                'code' => 409,
                'message' => __("validation.tasks-relations.must_have_same_project")
            ],
            self::CYCLIC => [
                'code' => 409,
                'message' => __("validation.tasks-relations.cyclic_relation_detected")
            ],
            self::ALREADY_EXISTS => [
                'code' => 409,
                'message' => __("validation.tasks-relations.already_exists")
            ],
            self::CANNOT_START_BEFORE_PARENT_ENDS => [
                'code' => 409,
                'message' => __("validation.tasks-relations.cannot_start_before_parent_ends")
            ]
        ];
        $this->errorCode = $type;
        $this->status = $ERRORS[$type]['code'];

        parent::__construct($ERRORS[$type]['message']);
    }
}
