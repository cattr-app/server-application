# Integration module creation (manual illustrated with Jira module)

Cattr uses [nwidart/laravel-modules](https://nwidart.com/laravel-modules) library to support modules. To create a module use the `php artisan module:make <module name>` command.

## Integration settings

### Model

To perform requests to Jira side, you'll need to store:

- integration's activity state and Jira's company host;
- API token per each user.

To store the additional non-tipisized properties, you can use the `App\Models\Property` model. Its fields are:

- `entity_type` (string) - model's type the property is related to. E.g. `company` or `user`;
- `entity_id` (int) - model's ID or 0;
- `name` (string) - property name;
- `value` (string) - property value.

Let's create a `Settings` helper to store the integration's settings in `Property`:

__Modules/JiraIntegration/Entities/Settings.php__

```php
<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Property;

class Settings
{
    // Property names
    public const ENABLED   = 'jira_enabled';
    public const API_HOST  = 'jira_api_host';
    public const API_TOKEN = 'jira_api_token';

    // Properties receiving
    public function get(string $entityType, int $entityId, string $propertyName, $default = '')
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'name'        => $propertyName,
        ];

        $property = Property::where($params)->first(['value']);

        return isset($property) ? $property->value : $default;
    }

    // Property install
    protected function set(string $entityType, int $entityId, string $propertyName, $value = ''): Property
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'name'        => $propertyName,
        ];

        return Property::updateOrCreate($params, ['value' => $value]);
    }

    // Activity integration's getter and setter

    public function getEnabled(): bool
    {
        return (bool)static::get(Property::COMPANY_CODE, 0, static::ENABLED, 0);
    }

    public function setEnabled(bool $enabled): Property
    {
        return static::set(Property::COMPANY_CODE, 0, static::ENABLED, $enabled);
    }

    // Jira host's getter and setter

    public function getHost(): string
    {
        return static::get(Property::COMPANY_CODE, 0, static::API_HOST, '');
    }

    public function setHost(string $key): Property
    {
        return static::set(Property::COMPANY_CODE, 0, static::API_HOST, $key);
    }

    // API user token's getter and setter

    public function getUserApiToken(int $userId): string
    {
        return static::get(Property::USER_CODE, $userId, static::API_TOKEN, '');
    }

    public function setUserApiToken(int $userId, string $token): Property
    {
        return static::set(Property::USER_CODE, $userId, static::API_TOKEN, $token);
    }
}
```

### Controllers

Let's create a controller to get and save company's settings. Controller template can be created with the `php artisan module:make-controller <controller name> <module name>` command.

__Modules/JiraIntegration/Http/Controllers/CompanySettingsController.php__

```php
<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Modules\JiraIntegration\Entities\Settings;

class CompanySettingsController extends Controller
{
    private $settings;

    // Get the Settings's instance via DI and save it to the $settings field
    public function __construct(Settings $settings)
    {
        parent::__construct();
        $this->settings = $settings;
    }

    // Endpoint controller's access rules
    public static function getControllerRules(): array
    {
        return [
            'get' => 'integration.jira-companysettings',
            'set' => 'integration.jira-companysettings',
        ];
    }

    // Get the integration's settings
    public function get()
    {
        return [
            'enabled' => $this->settings->getEnabled(),
            'host'    => $this->settings->getHost(),
        ];
    }

    // Save the integration's settings
    public function set(Request $request)
    {
        $this->settings->setEnabled($request->post('enabled'));
        $this->settings->setHost($request->post('host'));

        return response()->json(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
```

---

User settings' controller:

__Modules/JiraIntegration/Http/Controllers/CompanySettingsController.php__

```php
<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;
use Modules\JiraIntegration\Entities\Settings;

class SettingsController extends Controller
{
    private $settings;

    public function __construct(Settings $settings)
    {
        parent::__construct();
        $this->settings = $settings;
    }

    public static function getControllerRules(): array
    {
        return [
            'get' => 'integration.jira',
            'set' => 'integration.jira',
        ];
    }

    public function get(Request $request)
    {
        $userId = $request->user()->id;
        $apiToken = $this->settings->getUserApiToken($userId);

        return [
            // Hide the API token's part
            'api_token' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiToken),
        ];
    }

    public function set(Request $request)
    {
        // Validate the API token
        $validator = Validator::make($request->all(), ['api_token' => 'string|required']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Validation fail'], 400);
        }

        // Skip the token update, if it contains *
        if (strpos($request->post('api_token'), '*') !== false) {
            return response()->json(['success' => 'true', 'message' => 'Nothing to update!']);
        }

        $userId = $request->user()->id;
        $this->settings->setUserApiToken($userId, $request->post('api_token'));

        return response()->json(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
```

---

To let users call the `SettingsController`'s endpoints, we need to give them the `integration.jira` permission by default. To do that, we'll need to add the `role.actions.list` event subscription in the `JiraIntegrationServiceProvider`.

__Modules/JiraIntegration/Providers/JiraIntegrationServiceProvider.php__

```php
public function boot()
{
    $this->registerConfig();
    $this->registerCommands();
    $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

    Filter::listen('role.actions.list', static function ($rules) {
        if (!isset($rules['integration']['jira'])) {
            $rules['integration'] += ['jira' => __('Jira integration')];
        }

        return $rules;
    });
}
```

### Routing

__Modules/JiraIntegration/Routes/api.php__

```php
<?php

use \Illuminate\Routing\Router;

Route::middleware('auth:api')->group(function (Router $router) {
    $router->get('/settings', 'SettingsController@get')->name('settings.get');
    $router->post('/settings', 'SettingsController@set')->name('settings.set');
    $router->get('/companysettings', 'CompanySettingsController@get')->name('companysettings.get');
    $router->post('/companysettings', 'CompanySettingsController@set')->name('companysettings.set');
});
```

__Modules/JiraIntegration/Providers/RouteServiceProvider.php__

```php
<?php

namespace Modules\JiraIntegration\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        Route::middleware('api')
            ->as('v1.integration.jira.')
            ->prefix('v1/integration/jira')
            ->namespace('Modules\JiraIntegration\Http\Controllers')
            ->group(module_path('JiraIntegration', '/Routes/api.php'));
    }
}
```

## Jira projects and tasks sync

### Model

The `php artisan module:make-controller <controller name> <module name>` creates the model's template in the according module .

Let's create the `ProjectRelation` and `TaskRelation` models to compare the projects and tasks' IDs from Jira with Cattr's ones:

__Modules/JiraIntegration/Entities/ProjectRelation.php__

```php
<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectRelation extends Model
{
    // Table that stores the data
    protected $table = 'jira_projects_relation';

    // Fields that can be filled in while creating the model
    protected $fillable = [
        'id',
        'project_id',
    ];

    // Turns off the default created_at and updated_at fields use
    public $timestamps = false;

    // Connection with the App\Models\Project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
```

__Modules/JiraIntegration/Entities/TaskRelation.php__

```php
<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class TaskRelation extends Model
{
    protected $table = 'jira_tasks_relation';

    protected $fillable = [
        'id',
        'task_id',
    ];

    public $timestamps = false;

    // Connection with the App\Models\Task
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
```

### Migrations

The `php artisan module:make-migration <table name> <module name>` creates the module's migration.

Let's generate 2 migrations per each model: the first will create the table, the second will add the external key's limitations.

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJiraProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('jira_projects_relation', function (Blueprint $table) {
            // Jura's project ID can be used as a primary key
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('project_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jira_projects_relation');
    }
}
```

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJiraProjectConstraints extends Migration
{
    public function up()
    {
        Schema::table('jira_projects_relation', function (Blueprint $table) {
            // the external key's limitations provides the data ingertity
            // by not letting the ProjectRelation creation with the project missing from the system
            // and removing the ProjectRelation when the linked project is completely removed from the system
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('jira_projects_relation', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
    }
}
```

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJiraTasksTable extends Migration
{
    public function up()
    {
        Schema::create('jira_tasks_relation', function (Blueprint $table) {
            // Jira's task ID can be used as a primary key
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('task_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jira_tasks_relation');
    }
}
```

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJiraTaskConstraints extends Migration
{
    public function up()
    {
        Schema::table('jira_tasks_relation', function (Blueprint $table) {
            // the external key's limitations provides the data ingertity
            // by not letting the TaskRelation creation with the task missing from the system
            // and removing the TaskRelation when the linked task is completely removed from the system
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('jira_tasks_relation', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });
    }
}
```

### Services

To send the queries to Jira, you can use the [lesstif/php-jira-rest-client](https://github.com/lesstif/php-jira-rest-client) library.

Let's create a service that loads tasks from Jira:

__Modules/JiraIntegration/Services/SyncTasks.php__

```php
<?php

namespace Modules\JiraIntegration\Services;

use App\Models\{Project, Task, User};
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\{Issue, IssueService};
use JiraRestApi\JiraException;
use JiraRestApi\Project\Project as JiraProject;
use JiraRestApi\Project\ProjectService;
use Modules\JiraIntegration\Entities\{ProjectRelation, Settings, TaskRelation};

class SyncTasks
{
    protected $settings;
    protected $host;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        $this->host = $settings->getHost();
    }

    public function synchronizeAll()
    {
        // If the company integration isn't set, do the return
        if (empty($this->host) || !$this->settings->getEnabled()) {
            return;
        }

        // Sync the tasks for all the users
        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeAssignedIssues($user);
        }
    }

    public function synchronizeAssignedIssues(User $user)
    {
        // If the company integration for user's integration isn't set, do the return
        $token = $this->settings->getUserApiToken($user->id);
        if (empty($this->host) || empty($token)) {
            return;
        }

        // Use the company and user's settings
        // to init the IssueService and ProjectService
        $config = new ArrayConfiguration([
            'jiraHost'     => $this->host,
            'jiraUser'     => $user->email,
            'jiraPassword' => $token,
        ]);

        $issueService = new IssueService($config);
        $projectService = new ProjectService($config);

        try {
            // Load all the tasks assigned to the user
            // and create the tasks inside Cattr
            $issues = $this->getAssignedIssues($issueService, $user);
            foreach ($issues as $issue) {
                $this->synchronizeIssue($projectService, $user, $issue);
            }
        } catch(JiraException $e) {
            Log::error($e);
        }
    }

    protected function getAssignedIssues(IssueService $issueService, User $user): array
    {
        // Jira lets ask 100 tasks per once
        $query = "assignee = \"{$user->email}\"";
        $take = 100;

        $result = $issueService->search($query, 0, $take);
        $issues = $result->issues;
        $total = $result->total;

        for ($skip = $take; $skip < $total; $skip += $take) {
            $result = $issueService->search($query, $skip, $take);
            $issues = array_merge($issues, $result->issues);
        }

        return $issues;
    }

    protected function synchronizeIssue(ProjectService $projectService, User $user, Issue $issue)
    {
        $jiraProjectID = (int)$issue->fields->getProjectId();
        $projectRelation = ProjectRelation::find($jiraProjectID);
        if (!isset($projectRelation)) {
            // If the according ProjectRelation doesn't exist
            // create the project and ProjectRelation for it
            $jiraProject = $projectService->get($jiraProjectID);
            $projectData = $this->toInternalProjectData($jiraProject);
            $project = Project::create($projectData);

            $projectRelation = ProjectRelation::create([
                'id'         => $jiraProjectID,
                'project_id' => $project->id,
            ]);
        } else {
            $project = $projectRelation->project;
            if (!isset($project)) {
                // If the according ProjectRelation exists,
                // but not the project itself, create one
                $jiraProject = $projectService->get($jiraProjectID);
                $projectData = $this->toInternalProjectData($jiraProject);
                $project = Project::create($projectData);

                $projectRelation->project_id = $project->id;
                $projectRelation->save();
            }
        }

        $taskRelation = TaskRelation::find((int)$issue->id);
        if (!isset($taskRelation)) {
            // If the according TaskRelation exists,
            // create the task and TaskRelation for it
            $taskData = $this->toInternalTaskData($issue);
            $taskData['user_id'] = $user->id;
            $taskData['project_id'] = $projectRelation->project_id;
            $task = Task::create($taskData);

            TaskRelation::create([
                'id'      => (int)$issue->id,
                'task_id' => $task->id,
            ]);
        } else {
            $task = $taskRelation->task;
            if (!isset($task)) {
                // If the according TaskRelation exists,
                // but not the task itself, create one
                $taskData = $this->toInternalTaskData($issue);
                $taskData['user_id'] = $user->id;
                $taskData['project_id'] = $projectRelation->project_id;
                $task = Task::create($taskData);

                $taskRelation->task_id = $task->id;
                $taskRelation->save();
            }
        }
    }

    // Compare the JiraProject's data with the Cattr project's fields
    protected function toInternalProjectData(JiraProject $project): array
    {
        return [
            'company_id'  => 0,
            'name'        => $project->name,
            'description' => $project->description,
            'important'   => false,
            'source'      => 'jira', // Project source
        ];
    }

    // Compare the Jira Issue's data with the Cattr task's fields
    protected function toInternalTaskData(Issue $issue): array
    {
        return [
            'task_name'   => $issue->fields->summary,
            'description' => $issue->fields->description,
            'active'      => true,
            'assigned_by' => 0,
            'url'         => $issue->self,
            'created_at'  => $issue->fields->created,
            'updated_at'  => $issue->fields->updated,
            'priority_id' => 2,
            'important'   => false,
        ];
    }
}
```

---

Let's create a console command to call the task sync service. You can generate the console command's template with the `php artisan module:make-command <command name> <module name>` command.

__Modules/JiraIntegration/Console/SyncTasks.php__

```php
<?php

namespace Modules\JiraIntegration\Console;

use Illuminate\Console\Command;
use Modules\JiraIntegration\Services\SyncTasks as Service;

class SyncTasks extends Command
{
    protected $name = 'jira:sync-tasks';
    protected $description = 'Synchronize tasks from Jira for all users, who activate the Jira integration.';
    protected $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $this->service->synchronizeAll();
    }
}
```

---

Register the command in the `JiraIntegrationServiceProvider`:

__Modules/JiraIntegration/Providers/JiraIntegrationServiceProvider.php__

```php
protected function registerCommands()
{
    $this->commands([
        SyncTasks::class,
    ]);
}
```

---

Create the `ScheduleServiceProvider` to call the commands by the cron:

__Modules/JiraIntegration/Providers/ScheduleServiceProvider.php__

```php
<?php

namespace Modules\JiraIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping();
    }
}
```

---

Register the `ScheduleServiceProvider` in the `JiraIntegrationServiceProvider`:

```php
public function register()
{
    $this->app->register(RouteServiceProvider::class);
    $this->app->register(ScheduleServiceProvider::class);
}
```

## Tyme sync

### Model

Let's create a `TimeRelation` to store the intervals info that should be synced with Jira:

__Modules/JiraIntegration/Entities/TimeRelation.php__

```php
<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\{TimeInterval, User};
use Illuminate\Database\Eloquent\Model;

class TimeRelation extends Model
{
    protected $table = 'jira_time_relation';

    protected $fillable = [
        'jira_task_id',
        'time_interval_id',
        'user_id',
    ];

    public $timestamps = false;

    // TaskRelation link
    public function taskRelation()
    {
        return $this->belongsTo(TaskRelation::class, 'jira_task_id', 'id');
    }

    // App\Models\TimeInterval link
    public function timeInterval()
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id', 'id');
    }

    // App\Models\User link
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

---

Generate the model's migrations:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJiraTimeTable extends Migration
{
    public function up()
    {
        Schema::create('jira_time_relation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('jira_task_id');
            $table->unsignedInteger('time_interval_id');
            $table->unsignedInteger('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jira_time_relation');
    }
}
```

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJiraTimeConstraints extends Migration
{
    public function up()
    {
        Schema::table('jira_time_relation', function (Blueprint $table) {
            $table->foreign('jira_task_id')->references('id')->on('jira_tasks_relation')->onDelete('cascade');
            $table->foreign('time_interval_id')->references('id')->on('time_intervals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('jira_time_relation', function (Blueprint $table) {
            $table->dropForeign(['jira_task_id']);
            $table->dropForeign(['time_interval_id']);
            $table->dropForeign(['user_id']);
        });
    }
}
```

---

To automatically create the `TimeRelation` when creating the `TimeInterval`, you can subscribe to the `answer.success.item.create.timeinterval` event in the `JiraIntegrationServiceProvider`.

__Modules/JiraIntegration/Providers/JiraIntegrationServiceProvider.php__

```php
Filter::listen('answer.success.item.create.timeinterval', static function ($data) {
    $timeInterval = $data['interval'];
    // If the task has the TaskRelation,
    // i.e. it's synced with Jira,
    // create a TimeRelation object
    $taskRelation = TaskRelation::where(['task_id' => $timeInterval->task_id])->first();
    if (isset($taskRelation)) {
        TimeRelation::create([
            'jira_task_id'     => $taskRelation->id,
            'time_interval_id' => $timeInterval->id,
            'user_id'          => $timeInterval->user_id,
        ]);
    }

    return $data;
});
```

### Service

Create the `SyncTime` service to send the time intervals to Jira:

__Modules/JiraIntegration/Services/SyncTime.php__

```php
<?php

namespace Modules\JiraIntegration\Services;

use App\Models\{TimeInterval, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\{IssueService, Worklog};
use JiraRestApi\JiraException;
use Modules\JiraIntegration\Entities\{Settings, TaskRelation, TimeRelation};

class SyncTime
{
    protected $settings;
    protected $host;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        $this->host = $settings->getHost();
    }

    public function synchronizeAll()
    {
        // If the company integration isn't set, return
        if (empty($this->host) || !$this->settings->getEnabled()) {
            return;
        }

        // Time sync for all the users
        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeUserTime($user);
        }
    }

    public function synchronizeUserTime(User $user)
    {
        // If the user or the company integration isn't set, return
        $token = $this->settings->getUserApiToken($user->id);
        if (empty($this->host) || empty($token)) {
            return;
        }

        // Init the IssueService, using the company and user's settings
        $config = new ArrayConfiguration([
            'jiraHost'     => $this->host,
            'jiraUser'     => $user->email,
            'jiraPassword' => $token,
        ]);
        $issueService = new IssueService($config);

        // Get all the user's TimeRelation objects
        $timeRelations = TimeRelation::where('user_id', $user->id)->get();
        foreach ($timeRelations as $timeRelation) {
            // Get the task info
            $taskRelation = $timeRelation->taskRelation;
            $issueID = $taskRelation->id;

            // Get the interval info
            $timeInterval = $timeRelation->timeInterval;
            $startAt = Carbon::parse($timeInterval->start_at);
            $endAt = Carbon::parse($timeInterval->end_at);
            $duration = (int)$endAt->floatDiffInSeconds($startAt);

            // Does not send intervals, that shorter than one minute, as Jira REST API
            // rounds it down to minutes and does not accept zero-length intervals
            if ($duration < 60) {
                $timeRelation->delete();
                continue;
            }

            // Create and send the Worklog to Jira
            $workLog = new Worklog();
            $workLog->setStartedDateTime($startAt)->setTimeSpentSeconds($duration);

            try {
                $issueService->addWorklog($issueID, $workLog);

                // If the Worklog has been sent successfuly, remove the current TimeRelation object from the database
                $timeRelation->delete();
            } catch (JiraException $e) {
                Log::error($e);
            }
        }
    }
}
```

---

To call the time intervals send command, create the console command:

__Modules/JiraIntegration/Console/SyncTime.php__

```php
<?php

namespace Modules\JiraIntegration\Console;

use Illuminate\Console\Command;
use Modules\JiraIntegration\Services\SyncTime as Service;

class SyncTime extends Command
{
    protected $name = 'jira:sync-time';
    protected $description = 'Synchronize time to Jira for all users, who activate the Jira integration.';
    protected $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $this->service->synchronizeAll();
    }
}
```

---

Register the command in the `JiraIntegrationServiceProvider`:

__Modules/JiraIntegration/Providers/JiraIntegrationServiceProvider.php__

```php
protected function registerCommands()
{
    $this->commands([
        SyncTasks::class,
        SyncTime::class,
    ]);
}
```

---

Add the command call by cron in the `ScheduleServiceProvider`:

__Modules/JiraIntegration/Providers/ScheduleServiceProvider.php__

```php
public function boot()
{
    $schedule = $this->app->make(Schedule::class);
    $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping();
    $schedule->command('jira:sync-time')->everyMinute()->withoutOverlapping();
}
```
