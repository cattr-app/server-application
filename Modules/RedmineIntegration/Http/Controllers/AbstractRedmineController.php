<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Redmine;

abstract class AbstractRedmineController extends Controller
{
    protected $client;

    /**
     * Returns class name string
     *
     * Available class names located in Redmine\Client $classes array ('project', 'issue', 'user', ...)
     *
     * Abstract method that must be implemented when
     * sub-classing this class.
     */
    abstract public function getRedmineClientPropertyName();

    public function __construct()
    {
        //init Redmine client
        $this->client = new Redmine\Client(
            'https://redmine.amazingcat.net',
            '992435da9137d96e79d501b201946d1ed1d9a6b2'
        );
    }

    /**
     * Show info about entity
     * @param $id
     */
    public function show($id)
    {
        $name = $this->getRedmineClientPropertyName();

        dd($this->client->$name->show($id));
    }

    /**
     *  Show list of entities
     */
    public function list()
    {
        $name = $this->getRedmineClientPropertyName();

        dd($this->client->$name->all([
            'limit' => 1000
        ]));
    }


}
