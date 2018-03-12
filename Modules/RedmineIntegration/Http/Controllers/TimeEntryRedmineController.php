<?php

namespace Modules\RedmineIntegration\Http\Controllers;


class TimeEntryRedmineController extends AbstractRedmineController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets time entry with id == $id from Redmine
     *
     * @param $id
     * @return \Illuminate\Http\Response|void
     */
    public function show($id)
    {
        dd($this->client->time_entry->show($id));
    }

    /**
     * Gets list of time entries
     */
    public function list()
    {
        dd($this->client->time_entry->all([
            'limit' => 1000
        ]));
    }
}
