<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Redmine;

abstract class AbstractRedmineController extends Controller
{
    protected $client;

    abstract public function show($id);
    abstract public function list();

    public function __construct()
    {
        //init Redmine client
        $this->client = new Redmine\Client('https://redmine.amazingcat.net', '992435da9137d96e79d501b201946d1ed1d9a6b2');
    }


}
