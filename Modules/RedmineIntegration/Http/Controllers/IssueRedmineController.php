<?php

namespace Modules\RedmineIntegration\Http\Controllers;


class IssueRedmineController extends AbstractRedmineController
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
}
