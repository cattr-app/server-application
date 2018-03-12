<?php

namespace Modules\RedmineIntegration\Http\Controllers;


class ProjectRedmineController extends AbstractRedmineController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets project with id == $id from Redmine
     *
     * @param $id
     * @return \Illuminate\Http\Response|void
     */
    public function show($id)
    {
        dd($this->client->project->show($id));
    }

    /**
     * Gets list of projects
     */
    public function list()
    {
        dd($this->client->project->all([
            'limit' => 1000
        ]));
    }
}
