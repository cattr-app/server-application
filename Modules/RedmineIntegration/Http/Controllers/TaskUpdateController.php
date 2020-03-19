<?php /** @noinspection PhpMissingParentConstructorInspection */


namespace Modules\RedmineIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\RedmineIntegration\Events\TaskReceived;
use Modules\RedmineIntegration\Helpers\Plugin\PluginWebhookHelper;
use Throwable;

class TaskUpdateController extends Controller
{
    protected Request $request;
    protected PluginWebhookHelper $pluginWebhookHelper;

    public function __construct(
        Request $request,
        PluginWebhookHelper $pluginWebhookHelper
    ) {
        $this->request = $request;
        $this->pluginWebhookHelper = $pluginWebhookHelper;
    }

    public static function getControllerRules(): array
    {
        return [
            'handleUpdate' => 'integration.redmine',
        ];
    }

    /**
     * Accept task create/update from Redmine vie RedmineIntegration plugin
     *
     * @throws Exception
     */
    public function handleUpdate(): void
    {
        try {
            // Processing received task
            $task = $this->pluginWebhookHelper->process();

            // Fire an event for websocket clients
            event(new TaskReceived($task));
        } catch (Throwable $e) {
            Log::info($e->getMessage());
        }
    }
}
