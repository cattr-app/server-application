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
}
