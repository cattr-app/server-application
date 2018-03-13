<?php

namespace Modules\RedmineIntegration\Http\Controllers;


class TaskRedmineController extends AbstractRedmineController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets Issue with id == $id from Redmine
     *
     * @param $id
     * @return \Illuminate\Http\Response|void
     */
    public function show($id)
    {
        dd($this->client->issue->show($id));
    }

    /**
     * Gets list of issues
     */
    public function list()
    {
        dd($this->client->issue->all([
            'limit' => 1000
        ]));
    }

    /**
     * Returns issues from project with id == $projectId
     *
     * @param $projectId
     */
    public function getProjectIssues($projectId)
    {
        dd($this->client->issue->all([
            'project_id' => $projectId
        ]));
    }

    /**
     * Returns issues assigned to user with id == $userId
     *
     * @param $userId
     */
    public function getUserIssues($userId)
    {
        dd($this->client->issue->all([
            'assigned_to_id' => $userId
        ]));
    }
}
