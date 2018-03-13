<?php

namespace Modules\RedmineIntegration\Http\Controllers;


class UserRedmineController extends AbstractRedmineController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets user with id == $id from Redmine
     *
     * @param $id
     * @return \Illuminate\Http\Response|void
     */
    public function show($id)
    {
        dd($this->client->user->show($id));
    }

    /**
     * Gets list of users
     */
    public function list()
    {
        dd($this->client->user->all([
            'limit' => 1000
        ]));
    }

    public function synchronize()
    {
        $usersData = $this->client->user->all([
            'limit' => 1000
        ]);

        if ($usersData[0] == false) {
            return;
        }

        $users = $usersData['users'];

        foreach ($users as $userFromRedmine) {
            $userExist = RedmineUser::where('redmine_user_id', '=', $userFromRedmine['id'])->first();

            if ($userExist != null) {
                continue;
            }

            //$project = RedmineProject::where('redmine_project_id', '=', $userFromRedmine['project']['id'])->first();

            $taskInfo = [
                'full_name'  => $userFromRedmine['firstname'] . ' ' . $userFromRedmine['lastname'],
                'first_name' => $userFromRedmine['firstname'],
                'last_name'  => $userFromRedmine['lastname'],
                'email' => $userFromRedmine['mail'],
                'url' => 'URL',
                'company_id' => 1,
                'level' => 'user',
                'payroll_access' => 0,
                'billing_access' => 0,
                'avatar' => 'URL',
                'screenshots_active' => 1,
                'manual_time' => 10,
                'permanent_tasks' => 10,
                'computer_time_popup' => 0,
                'poor_time_popup' => 'No',
                'blur_screenshots' => 1,
                'web_and_app_monitoring' => 0,
                'webcam_shots' => 0,
                'screenshots_interval' => 0,
                'user_role_value' => 'user',
                'active' => 'active',
                'password' => 'password'
            ];

            $task = Task::create($taskInfo);

            RedmineTask::create(['task_id' => $task->id, 'redmine_task_id' => $taskFromRedmine['id']]);
        }
    }
}
