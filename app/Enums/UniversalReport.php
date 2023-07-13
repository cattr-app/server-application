<?php

namespace App\Enums;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

enum UniversalReport: string
{
    case PROJECT = 'project';
    case USER = 'user';
    case TASK = 'task';


    public function fields() {
        return match($this) {
            self::PROJECT => [
                'main' => [
                    'name',
                    'created_at',
                    'description',
                    'important',
                ],
                'tasks' => [
                    'task_name',
                    'priority',
                    'status',
                    'due_date',
                    'estimate',
                    'description',
                ],
                'users' => [
                    'full_name',
                    'email',
                ],
                // 'work_time',
                // 'work_time_user',
                'calculations' => [
                    'total_spent_time',
                    'total_spent_time_by_user',
                    'total_spent_time_by_day',
                    'total_spent_time_by_day_and_user',
                ],

            ],
            self::USER => [
                'main' => [
                    'full_name',
                    'email',
                ],
                'projects' => [
                    'name',
                    'created_at',
                    'description',
                    'important',
                ],
                'tasks' => [
                    'task_name',
                    'priority',
                    'status',
                    'due_date',
                    'estimate',
                    'description',
                ],
                'calculations' => [
                    'total_spent_time',
                    'total_spent_time_by_day',
                ]
            ],
            self::TASK => [
                'main' => [
                    'task_name',
                    'priority',
                    'status',
                    'due_date',
                    'estimate',
                    'description',
                    // 'workers',
                ],
                'users' => [
                    'full_name',
                    'email',
                ],
                'projects' => [
                    'name',
                    'created_at',
                    'description',
                    'important',
                ],
                'calculations' => [
                    'total_spent_time',
                    'total_spent_time_by_day',
                    'total_spent_time_by_user',
                    'total_spent_time_by_day_and_user'
                ],
                // 'total_spent_time',
                // 'user_name',
                // 'user_time',
            ],
        };
    }

    public function dataObjects()
    {
        return match($this) {
            self::PROJECT => (function() {
                if(!request()->user()->isAdmin()) {
                    return request()->user()->projects()->select('id', 'name')->get();
                }

                return Project::select('id', 'name')->get(); 
            })(),
            self::USER => (function() {
                if (!request()->user()->isAdmin()) {
                    return;
                }
                
                return User::select('id', 'full_name as name', 'email', 'full_name')->get();
            })(),
            self::TASK => (function() {
                if(!request()->user()->isAdmin()) {
                    return request()->user()->tasks()->select('id', 'name')->get();
                }

                return Task::select('id', 'task_name as name')->get(); 
            })()
        };
    }
    public function charts() {
        // [
        //     // Project
        //     "worked_all_users",// "Отработанное время всеми пользователями на проекте за указанный период",
        //     "worked_all_users_separately",// "Отработанное время каждым пользователем на проекте за указанный период",
        //     // Task
        //     'worked_all_users',// "Отработанное время всеми пользователями на задаче за указанный период",
        //     'worked_all_users_separately',// "Отработанное время каждым пользователем на задаче за указанный период",
        //     // User
        //     'total_hours',// "Всего часов за указанный период",
        //     'hours_tasks',// "Часов на каждой задаче",
        //     'hours_projects',// "Часов на каждом проекте",
            
        // ];
        return match($this) {
            self::PROJECT => [
                'worked_all_users', 
                'worked_all_users_separately',
            ],
            self::USER => [
                'total_hours',
                'hours_tasks', 
                'hours_projects',
            ],
            self::TASK => [
                'worked_all_users',
                'worked_all_users_separately',
            ],
        };
    }

    public static function mains()
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public function checkAccess(array $data_objects)
    {
        $user = request()->user();
        return match($this) {
            self::PROJECT => $user->projects()->select('id')->whereIn('id', $data_objects)->get()->count() === count($data_objects) ? true : false,
            self::USER => '',
            self::TASK => $user->tasks()->select('id')->whereIn('id', $data_objects)->get()->count() === count($data_objects) ? true : false,
        };
    }

    public function model()
    {
        return match($this) {
            self::PROJECT => new Project(),
            self::USER => new User(),
            self::TASK => new Task(),
        };
    }
}
