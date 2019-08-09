<?php


namespace Modules\RedmineIntegration\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskUpdateController extends Controller
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * TaskUpdateController constructor.
     *
     * @param  Request  $request
     */
    public function __construct(
        Request $request
    )
    {
        $this->request = $request;
    }

    /**
     * Accept task create/update from Redmine vie RedmineIntegration plugin
     *
     * @return void
     */
    public function handleUpdate(): void
    {
        echo 'hello';
    }
}
