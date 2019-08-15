<?php


namespace Modules\RedmineIntegration\Http\Controllers;


use App\Http\Controllers\Controller;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\RedmineIntegration\Events\TaskReceived;
use Modules\RedmineIntegration\Helpers\Plugin\PluginWebhookHelper;
use Throwable;

class TaskUpdateController extends Controller
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var PluginWebhookHelper
     */
    protected $pluginWebhookHelper;

    /**
     * TaskUpdateController constructor.
     *
     * @param  Request              $request
     * @param  PluginWebhookHelper  $pluginWebhookHelper
     */
    public function __construct(
        Request $request,
        PluginWebhookHelper $pluginWebhookHelper
    ) {
        $this->request = $request;
        $this->pluginWebhookHelper = $pluginWebhookHelper;
    }

    /**
     * Accept task create/update from Redmine vie RedmineIntegration plugin
     *
     * @return void
     * @throws Exception
     */
    public function handleUpdate(): void
    {
        try {
            $task = $this->pluginWebhookHelper->process();

            event(new TaskReceived($task));
        } catch (Throwable $e) {
            Log::info($e->getMessage());
        }
    }
}
