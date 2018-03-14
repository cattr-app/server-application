<?php

namespace Modules\RedmineIntegration\Http\Controllers;

class UserRedmineController extends AbstractRedmineController
{
    /**
     * UserRedmineController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns class name string
     *
     * @return string
     */
    public function getRedmineClientPropertyName()
    {
        return 'user';
    }
}
