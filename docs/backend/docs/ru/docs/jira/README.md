# Создания модуля интеграции на примере Jira

Для поддержки модулей в Cattr используется библиотека nwidart/laravel-modules. Создать новый модуль, с её помощью, можно командой php artisan module:make <название модуля>.

## Настройки интеграции

### Модель

Для запросов к Jira потребуется хранить:

    состояние активности интеграции и адрес хоста Jira для компании;
    токен API для каждого пользователя.

Для хранения дополнительных нетипизированных свойств в Cattr существует модель App\Models\Property. Её поля:
user;
entity_id (int) - ID модели или 0;
name (string) - название свойства;
value (string) - значение свойства.

Создадим хелпер Settings для хранения настроек интеграции в Property:

### Modules/JiraIntegration/Entities/Settings.php

```php
<?php

<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Property;

class Settings
{
    // Названия свойств
    public const API_HOST = 'jira_api_host';
    public const API_TOKEN = 'jira_api_token';
    public const API_EMAIL = 'jira_email';

    // Получение свойства
    public function get(string $entityType, int $entityId, string $propertyName, $default = '')
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'name' => $propertyName,
        ];

        $property = Property::where($params)->first(['value']);

        return $property ? $property->value : $default;
    }

    // Установка свойства
    protected function set(string $entityType, int $entityId, string $propertyName, $value = ''): Property
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'name' => $propertyName,
        ];

        return Property::updateOrCreate($params, ['value' => $value]);
    }

    // Геттер и сеттер хоста Jira

    public function getHost(): string
    {
        return $this->get(Property::COMPANY_CODE, 0, static::API_HOST, '');
    }

    public function setHost(string $key): Property
    {
        return $this->set(Property::COMPANY_CODE, 0, static::API_HOST, $key);
    }

    // Геттер и сеттер токена API пользователя

    public function getUserApiToken(int $userId): string
    {
        return $this->get(Property::USER_CODE, $userId, static::API_TOKEN, '');
    }

    public function setUserApiToken(int $userId, string $key): Property
    {
        return $this->set(Property::USER_CODE, $userId, static::API_TOKEN, $key);
    }

    public function setEmail(int $userId, string $key): Property
    {
        return $this->set(Property::USER_CODE, $userId, static::API_EMAIL, $key);
    }

    public function getEmail(int $userId): string
    {
        return $this->get(Property::USER_CODE, $userId, static::API_EMAIL);
    }
}

```

Создадим роутер web.php

```bash
touch modules/JiraIntegration/Routes/web.php
```

### Контроллеры

Создадим контроллер для получения и сохранения настроек компании. Шаблон контроллера можно создать командой php artisan module:make-controller <название контроллера> <название модуля>.

##### Modules/JiraIntegration/Http/Controllers/SettingsController.php

```php
<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JiraIntegration\Services\SettingsService;
use Modules\JiraIntegration\Http\Requests\Setting\UpdateSettingsRequest;


class SettingsController extends Controller
{
    /**
     * SettingsController constructor.
     * @param SettingsService $settings
     */

    // Получаем экземпляр Settings через DI и сохраняем его в поле $settings
    public function __construct(private SettingsService $settings)
    {
    }

    /**
     * @return array
     */

    // Права для доступа к эндпоинтам контроллера
    public static function getControllerRules(): array
    {
        return [
            'index' => 'integration.jira-settings',
            'update' => 'integration.jira-settings',
        ];
    }

    /**
     * @return array
     */
    public function index(): array
    {
        $settings = $this->settings->all();

        return [
            'success' => true,
            'data' => $settings
        ];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->settings->set($request->validated());

        return new JsonResponse([
            'success' => true,
            'message' => 'Settings saved successfully'
        ]);
    }
}

```

Контроллер для настроек пользователя:

#### Modules/JiraIntegration/Http/Controllers/UserSettingsController.php

```php
<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JiraIntegration\Entities\Settings;
use Modules\JiraIntegration\Http\Requests\Setting\UpdateUserSettingsRequest;
use Modules\JiraIntegration\Services\SettingsService;

class UserSettingsController extends Controller
{
    private Settings $userSettings;
    private SettingsService $settings;

    public function __construct(Settings $userSettings, SettingsService $settings)
    {

        $this->userSettings = $userSettings;
        $this->settings = $settings;
    }

    public static function getControllerRules(): array
    {
        return [
            'index' => 'integration.jira',
            'update' => 'integration.jira',
        ];
    }

    public function index(Request $request): array
    {
        $userId = $request->user()->id;
        $apiToken = $this->userSettings->getUserApiToken($userId);
        $email = $this->userSettings->getEmail($userId);

        return [
            'success' => true,
            'data' => [
                'enabled' => $this->settings->isEnabled(),
                'email' => $email,
                 // Скрываем часть токена API
                'api_token' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiToken),
            ]
        ];
    }

    public function update(UpdateUserSettingsRequest $request): JsonResponse
    {
        $requestData = $request->validated();
        $userId = $request->user()->id;
        $token = $request->post('api_token');

        // Не обновляем токен, если он содержит *
        if (strpos($token, '*') !== false) {
            $token = $this->userSettings->getUserApiToken($userId);
            if ($this->userSettings->getEmail($userId) === $requestData['email']) {
                return new JsonResponse(['success' => 'true', 'message' => 'Nothing to update!']);
            }
        }

        $userId = $request->user()->id;
        $this->userSettings->setUserApiToken($userId, $token);
        $this->userSettings->setEmail($userId, $requestData['email']);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'enabled' => $this->settings->isEnabled(),
                'email' => $requestData['email'],
                'api_token' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $token),
            ],
        ]);
    }
}

```

Чтобы изменить стандартную логику catr дополнить или изменить используется подписка на событие. Для этого можно в ModuleServiceProvider подписаться на список событий EventObserver

#### Modules/JiraIntegration/Providers/JiraIntegrationServiceProvider.php

```php
    public static function registerEvents(): void
        {
            CatEvent::subscribe(EventObserver::class);
        }

```

#### Modules/JiraIntegration/Subscribers/EventObserver

```php
public function subscribe(): array
    {

        return [
            'event.after.action.tasks.list' => [[__CLASS__, 'taskList']],
            'event.after.action.tasks.edit' => [[__CLASS__, 'taskEdition']],
            'event.after.action.tasks.destroy' => [[__CLASS__, 'taskDeletion']],
            'event.after.action.intervals.create' => [[__CLASS__, 'intervalCreate']],
        ];
    }

```

### Роутинг

#### Modules/JiraIntegration/Routes/api.php

```php
<?php

use Illuminate\Routing\Router;

Route::middleware('auth:sanctum')->group(static function (Router $router) {
    $router->get('/settings', 'SettingsController@index')->name('settings.index');
    $router->patch('/settings', 'SettingsController@update')->name('settings.update');
    $router->get('/user-settings', 'UserSettingsController@index')->name('user-settings.index');
    $router->patch('/user-settings', 'UserSettingsController@update')->name('user-settings.update');
});
```

## Синхронизация проектов и задач из Jira

### Модель

Команда php artisan module:make-model <название модели> <название модуля> создаёт заготовку модели в указанном модуле.

Создадим модели ProjectRelation и TaskRelation для сопоставления ID проектов и задач в Jira внутренним проектам и задачам:

Modules/JiraIntegration/Entities/ProjectRelation.php

```php

<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 *
 * @property Project $project
 */
class ProjectRelation extends Model
{
    // Отключает использование стандартных полей created_at и updated_at
    public $timestamps = false;

    // Таблица, в которой будут храниться данные
    protected $table = 'jira_projects_relation';
    // Поля, которые можно заполнять при создании модели и т.д.
    protected $fillable = [
        'id',
        'project_id',
    ];

    protected $casts = [
        'project_id' => 'integer',
    ];


    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}

```

### Modules/JiraIntegration/Entities/TaskRelation.php

```php
<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 *
 * @property Task $task
 */
class TaskRelation extends Model
{
    public $timestamps = false;

    protected $table = 'jira_tasks_relation';

    protected $fillable = [
        'id',
        'task_id',
    ];

    protected $casts = [
        'task_id' => 'integer',
    ];

  // Связь с App\Models\Task
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
```

## Миграции

Команда php artisan module:make-migration <название таблицы> <название модуля> создаёт миграцию в модуле.

Сгенерируем для каждой модели две миграции: в первой создадим таблицу, во второй установим ограничения внешнего ключа.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJiraTasksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jira_tasks_relation', static function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_tasks_relation');
    }
}
```

### Сервисы

Для отправки запросов к Jira можно использовать библиотеку lesstif/php-jira-rest-client.

Создадим сервис для загрузки задач из Jira:

Modules/JiraIntegration/Services/SyncTasks.php

```php
<?php

namespace Modules\JiraIntegration\Services;

use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Worklog;
use JiraRestApi\JiraException;
use Modules\JiraIntegration\Entities\LostTime;
use Modules\JiraIntegration\Entities\Settings;
use Modules\JiraIntegration\Entities\TimeRelation;

class SyncTime
{
    protected Settings $userSettings;
    protected SettingsService $settings;

    public function __construct(Settings $userSettings, SettingsService $settings)
    {
        $this->userSettings = $userSettings;
        $this->settings = $settings;
    }

    public function synchronizeAll(): void
    {
        // Если интеграция не настроена для компании, выходим
        $host = $this->settings->getHostUrl();
        if (empty($host) || !$this->settings->isEnabled()) {
            return;
        }
        // Синхронизируем задачи для всех пользователей
        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeUserTime($user);
        }
    }

    public function synchronizeUserTime(User $user): void
    {   // Если интеграция не настроена для компании или пользователя, выходим
        $host = $this->settings->getHostUrl();
        $token = $this->userSettings->getUserApiToken($user->id);
        if (empty($host) || empty($token)) {
            return;
        }
        // Используем настройки компании и пользователя
        // для инициализации IssueService и ProjectService
        $config = new ArrayConfiguration([
            'jiraHost' => $host,
            'jiraUser' => $this->userSettings->getEmail($user->id),
            'jiraPassword' => $token,
        ]);

        $issueService = new IssueService($config);
        $timeRelations = TimeRelation::where('user_id', $user->id)->get();
        foreach ($timeRelations as $timeRelation) {
            /** @var TimeRelation $timeRelation */
            $taskRelation = $timeRelation->taskRelation;
            $issueID = $taskRelation->id;

            /** @var TimeInterval $timeInterval */
            $timeInterval = $timeRelation->timeInterval;
            if (!isset($timeInterval)) {
                $timeRelation->delete();
                continue;
            }

            $startAt = Carbon::parse($timeInterval->start_at);
            $endAt = Carbon::parse($timeInterval->end_at);
            $duration = (int)$endAt->floatDiffInSeconds($startAt);

            /** @var LostTime $lostTime */
            $lostTime = LostTime::where([
                ['jira_task_id', '=', $timeRelation->jira_task_id],
                ['user_id', '=', $user->id]
            ])->get()->first();

            if ($lostTime) {
                $duration += $lostTime->seconds;
            }
           // Jira REST API не принимает интервалы меньше одной минуты.
           // Когда интервал времени меньше минуты,
           // мы сохраняем его в базу данных.
            if ($duration < 60) {
                LostTime::updateOrCreate([
                    'jira_task_id' => $timeRelation->jira_task_id,
                    'user_id' => $user->id,
                ], [
                    'seconds' => $duration,
                ]);

                $timeRelation->delete();
                continue;
            }

            // Когда продолжительность временного интервала не кратна одной минуте,
            // мы сохраняем остаток в базу данных.
            if ($duration % 60) {
                $lostTimeDuration = $duration % 60;
                $duration -= $lostTimeDuration;
                LostTime::updateOrCreate([
                    'user_id' => $user->id,
                    'jira_task_id' => $timeRelation->jira_task_id,
                ], [
                    'seconds' => $lostTimeDuration,
                ]);
            } else {
                // Если существует потерянное время
                // и сумма интервалов кратна одной минуте,
                // тогда удалите потерянное время из базы данных.
                $lostTime && $lostTime->delete();
            }

            $workLog = new Worklog();
            $workLog->setStartedDateTime($startAt)->setTimeSpentSeconds($duration);

            try {
                  // Загрузим все задачи, назначенные пользователю,
                  // и создадим для них внутренние задачи
                $issueService->addWorklog($issueID, $workLog);
                $timeRelation->delete();
            } catch (JiraException $e) {
                Log::error($e->getMessage());
            }
        }
    }
}


```

Создадим консольную команду для вызова сервиса синхронизации задач. Сгенерировать шаблон консольной команды можно выполнив команду php artisan module:make-command <название команды> <название модуля>.

### Modules/JiraIntegration/Console/SyncTasks.php

```php
<?php

namespace Modules\JiraIntegration\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JiraRestApi\Project\Project as JiraProject;
use JiraRestApi\Project\ProjectService;
use Modules\JiraIntegration\Entities\ProjectRelation;
use Modules\JiraIntegration\Entities\Settings;
use Modules\JiraIntegration\Entities\TaskRelation;

class SyncTasks
{
    protected Settings $userSettings;
    protected SettingsService $settings;

    public function __construct(Settings $userSettings, SettingsService $settings)
    {
        $this->userSettings = $userSettings;
        $this->settings = $settings;
    }

    public function synchronizeAll(): void
    {
        $host = $this->settings->getHostUrl();
        if (empty($host) || !$this->settings->isEnabled()) {
            return;
        }

        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeAssignedIssues($user);
        }
    }

    public function synchronizeAssignedIssues(User $user): void
    {
        $host = $this->settings->getHostUrl();
        $token = $this->userSettings->getUserApiToken($user->id);
        $email = $this->userSettings->getEmail($user->id);

        if (empty($host) || empty($token) || empty($email)) {
            return;
        }

        $config = new ArrayConfiguration([
            'jiraHost' => $host,
            'jiraUser' => $email,
            'jiraPassword' => $token,
        ]);

        $issueService = new IssueService($config);
        $projectService = new ProjectService($config);

        try {
            $issues = $this->getAssignedIssues($issueService, $user);
            foreach ($issues as $issue) {
                $this->synchronizeIssue($projectService, $user, $issue);
            }
        } catch (JiraException $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * @param IssueService $issueService
     * @param User $user
     *
     * @return Issue[]
     */
    protected function getAssignedIssues(IssueService $issueService, User $user): array
    {
        $query = "assignee = \"{$this->userSettings->getEmail($user->id)}\"";
        $take = 100;

        try {
            $result = $issueService->search($query, 0, $take);
            $issues = $result->issues;
            $total = $result->total;
        } catch (JiraException $e) {
            throw new JiraException($e->getMessage());
        }

        for ($skip = $take; $skip < $total; $skip += $take) {
            $result = $issueService->search($query, $skip, $take);
            $issues = array_merge($issues, $result->issues);
        }

        return $issues;
    }

    protected function synchronizeIssue(ProjectService $projectService, User $user, Issue $issue): void
    {
        $jiraProjectID = (int)$issue->fields->getProjectId();
        $jiraProject = $projectService->get($jiraProjectID);
        $projectData = $this->toInternalProjectData($jiraProject);

        /** @var ProjectRelation $projectRelation */
        $projectRelation = ProjectRelation::find($jiraProjectID);
        if (isset($projectRelation)) {
            // Если соответствущий ProjectRelation не существует,
            // создадим проект и ProjectRelation
            $project = $projectRelation->project;
            if (isset($project)) {
                 // Если соответствующий ProjectRelation существует,
                // а проект нет, создадим проект
                $project->fill($projectData);
                $project->save();
            } else {
                $project = Project::create($projectData);

                $projectRelation->project_id = $project->id;
                $projectRelation->save();
            }
        } else {
            $project = Project::create($projectData);

            $projectRelation = ProjectRelation::create([
                'id' => $jiraProjectID,
                'project_id' => $project->id,
            ]);
        }

        $taskData = $this->toInternalTaskData($issue);
        $taskData['user_id'] = $user->id;
        $taskData['project_id'] = $projectRelation->project_id;

        /** @var TaskRelation $taskRelation */
        $taskRelation = TaskRelation::find((int)$issue->id);
        if (isset($taskRelation)) {
            // Если соответствущий TaskRelation не существует,
            // создадим задачу и TaskRelation
            $task = $taskRelation->task;
            if (isset($task)) {
                /** @var Task $task */
                $task->fill($taskData);
                $task->save();
                $task->users()->sync([$user->id]);
            } else {
                /** @var Task $task */
                $task = Task::create($taskData);
                $task->users()->sync([$user->id]);

                $taskRelation->task_id = $task->id;
                $taskRelation->save();
            }
        } else {
            // Если соответствующий TaskRelation существует,
            // а задача нет, создадим задачу
            $task = Task::create($taskData);
            $task->users()->sync([$user->id]);

            TaskRelation::create([
                'id' => (int)$issue->id,
                'task_id' => $task->id,
            ]);
        }
    }
     // Сопоставляем данные JiraProject полям внутреннего проекта
    protected function toInternalProjectData(JiraProject $project): array
    {
        return [
            'company_id' => 0,
            'name' => $project->name,
            'description' => $project->description,
            'important' => false,
            'source' => 'jira',
        ];
    }
     // Сопоставляем данные Issue полям внутренней задачи
    protected function toInternalTaskData(Issue $issue): array
    {
        return [
            'task_name' => $issue->fields->summary,
            'description' => $issue->fields->description,
            'active' => !isset($issue->fields->resolution),
            'assigned_by' => 0,
            'url' => "{$this->settings->getHostUrl()}/browse/{$issue->key}",
            'created_at' => $issue->fields->created,
            'updated_at' => $issue->fields->updated,
            'priority_id' => 2,
            'important' => false,
            'status_id' => 1,
        ];
    }
}
```

Создадим консольную команду для вызова сервиса синхронизации задач. Сгенерировать шаблон консольной команды можно выполнив команду

```bash
php artisan module:make-command <название команды> <название модуля>.
```

### Modules/JiraIntegration/Console/SyncTasks.php

```php
<?php

namespace Modules\JiraIntegration\Console;

use Illuminate\Console\Command;
use Modules\JiraIntegration\Services\SyncTasks as Service;

class SyncTasks extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'jira:sync-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tasks from Jira for all users, who activate the Jira integration.';

    /**
     * Create a new command instance.
     *
     * @param Service $service
     */
    public function __construct(protected Service $service)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->service->synchronizeAll();
    }
}
```

В ModuleServiceProvider необходимо зарегестрировать команду:

#### Modules/JiraIntegration/Providers/ModuleServiceProvider.php

```php
    /**
     * Register command
     */
    protected function registerCommands(): void
    {
        $this->commands([
            SyncTasks::class,
            SyncTime::class,
        ]);
    }
```

Создадим ScheduleServiceProvider для вызова команд по cron:

### Modules/JiraIntegration/Providers/ScheduleServiceProvider.php

```php

    <?php

    namespace Modules\JiraIntegration\Providers;

    use Illuminate\Console\Scheduling\Schedule;
    use Illuminate\Support\ServiceProvider;

    class ScheduleServiceProvider extends ServiceProvider
    {
        public function boot(): void
        {
            $this->app->booted(function () {
                $schedule = app(Schedule::class);
                $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping();
                $schedule->command('jira:sync-time')->everyMinute()->withoutOverlapping();
            });
        }
    }

```

Регистрация ScheduleServiceProvider в ModuleServiceProvider:

```php

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(ScheduleServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

```

## Синхронизация времени

### Модель

Создадим модель TimeRelation для хранения информации об интервалах, которые должны быть синхронизированы в Jira:

```php

<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $jira_task_id
 * @property int $time_interval_id
 * @property int $user_id
 *
 * @property TaskRelation $taskRelation
 * @property TimeInterval $timeInterval
 * @property User $user
 */
class TimeRelation extends Model
{
    public $timestamps = false;

    protected $table = 'jira_time_relation';

    protected $fillable = [
        'jira_task_id',
        'time_interval_id',
        'user_id',
    ];

    protected $casts = [
        'jira_task_id' => 'integer',
        'time_interval_id' => 'integer',
        'user_id' => 'integer',
    ];

    // Связь с TaskRelation
    public function taskRelation(): BelongsTo
    {
        return $this->belongsTo(TaskRelation::class, 'jira_task_id', 'id');
    }

    // Связь с App\Models\TimeInterval
    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id', 'id');
    }

    // Связь с App\Models\User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

Сгенерируем миграции для модели:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJiraTimeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jira_time_relation', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('jira_task_id');
            $table->unsignedInteger('time_interval_id');
            $table->unsignedInteger('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_time_relation');
    }
}
```

Чтобы автоматически создавать объекты TimeRelation при создании TimeInterval, можно в ModuleServiceProvider подписаться на событие event.after.action.intervals.create:

### Modules/JiraIntegration/Providers/ModuleServiceProvider.php

```php
    public static function registerEvents(): void
        {
            CatEvent::subscribe(EventObserver::class);
        }
```

### Modules/JiraIntegration/Providers/EventObserver.php

```php
 public function intervalCreate($data)
    {
         // Если для задачи существует отношение TaskRelation,
         // т.е. она синхронизирована из Jira,
         // создадим объект TimeRelation
        $taskRelation = TaskRelation::where(['task_id' => $data['task_id']])->first();

        if (isset($taskRelation)) {
            TimeRelation::create([
                'jira_task_id' => $taskRelation->id,
                'time_interval_id' => $data['id'],
                'user_id' => $data['user_id'],
            ]);
        }

        return $data;
    }
```

### Сервис

Создадим сервис SyncTime для отправки времени в Jira:

#### Modules/JiraIntegration/Services/SyncTime.php

```php
<?php

namespace Modules\JiraIntegration\Services;

use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Worklog;
use JiraRestApi\JiraException;
use Modules\JiraIntegration\Entities\LostTime;
use Modules\JiraIntegration\Entities\Settings;
use Modules\JiraIntegration\Entities\TimeRelation;

class SyncTime
{
    protected Settings $userSettings;
    protected SettingsService $settings;

    public function __construct(Settings $userSettings, SettingsService $settings)
    {
        $this->userSettings = $userSettings;
        $this->settings = $settings;
    }

    public function synchronizeAll(): void
    {
        // Если интеграция не настроена для компании, выходим
        $host = $this->settings->getHostUrl();
        if (empty($host) || !$this->settings->isEnabled()) {
            return;
        }

        // Синхронизируем время для всех пользователей
        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeUserTime($user);
        }
    }

    public function synchronizeUserTime(User $user): void
    {
        // Если интеграция не настроена для компании или пользователя, выходим
        $host = $this->settings->getHostUrl();
        $token = $this->userSettings->getUserApiToken($user->id);
        if (empty($host) || empty($token)) {
            return;
        }
        // Инициализируе IssueService, используя настройки компании и пользователя
        $config = new ArrayConfiguration([
            'jiraHost' => $host,
            'jiraUser' => $this->userSettings->getEmail($user->id),
            'jiraPassword' => $token,
        ]);
         // Получим все объекты TimeRelation пользователя
        $issueService = new IssueService($config);
        $timeRelations = TimeRelation::where('user_id', $user->id)->get();
        foreach ($timeRelations as $timeRelation) {
             // Получаем информацию о задаче
            $taskRelation = $timeRelation->taskRelation;
            $issueID = $taskRelation->id;

            // Получаем информацию об итервале
            $timeInterval = $timeRelation->timeInterval;
            if (!isset($timeInterval)) {
                $timeRelation->delete();
                continue;
            }

            $startAt = Carbon::parse($timeInterval->start_at);
            $endAt = Carbon::parse($timeInterval->end_at);
            $duration = (int)$endAt->floatDiffInSeconds($startAt);

            /** @var LostTime $lostTime */
            $lostTime = LostTime::where([
                ['jira_task_id', '=', $timeRelation->jira_task_id],
                ['user_id', '=', $user->id]
            ])->get()->first();

            if ($lostTime) {
                $duration += $lostTime->seconds;
            }
            // Jira REST API не принимает интервалы короче одной минуты.
            // Когда временной интервал меньше одной минуты,
            // мы сохраняем его в базе данных.
            if ($duration < 60) {
                LostTime::updateOrCreate([
                    'jira_task_id' => $timeRelation->jira_task_id,
                    'user_id' => $user->id,
                ], [
                    'seconds' => $duration,
                ]);

                $timeRelation->delete();
                continue;
            }

            // Когда продолжительность временного интервала не кратна одной минуте,
            // тогда мы сохраняем остаток в базе данных.
            if ($duration % 60) {
                $lostTimeDuration = $duration % 60;
                $duration -= $lostTimeDuration;
                LostTime::updateOrCreate([
                    'user_id' => $user->id,
                    'jira_task_id' => $timeRelation->jira_task_id,
                ], [
                    'seconds' => $lostTimeDuration,
                ]);
            } else {
                // Если существует потерянное время
                // и сумма интервалов кратна одной минуте,
                // то удалите потерянное время из базы данных.
                $lostTime && $lostTime->delete();
            }

             // Создаём и отправляем Worklog в Jira
            $workLog = new Worklog();
            $workLog->setStartedDateTime($startAt)->setTimeSpentSeconds($duration);

            try {
                $issueService->addWorklog($issueID, $workLog);

                // Если успешно отправлено, удаляем текущий объект TimeRelation из БД
                $timeRelation->delete();
            } catch (JiraException $e) {
                Log::error($e->getMessage());
            }
        }
    }
}

```

Для вызова сервиса отправки времени создадим консольную команду:

#### Modules/JiraIntegration/Console/SyncTime.php

```php
<?php

namespace Modules\JiraIntegration\Console;

use Illuminate\Console\Command;
use Modules\JiraIntegration\Services\SyncTime as Service;

class SyncTime extends Command
{
    protected $name = 'jira:sync-time';

    protected $description = 'Synchronize time to Jira for all users, who activate the Jira integration.';

    public function __construct(protected Service $service)
    {
        parent::__construct();
    }
    public function handle(): void
    {
        $this->service->synchronizeAll();
    }
}

```

Зарегистрируем команду в ModuleServiceProvider:

#### Modules/JiraIntegration/Providers/ModuleServiceProvider.php

```php
protected function registerCommands(): void
    {
        $this->commands([
            SyncTasks::class,
            SyncTime::class,
        ]);
    }

```

Добавим вызов команды по cron в ScheduleServiceProvider:

#### Modules/JiraIntegration/Providers/ScheduleServiceProvider.php

```php

<?php

namespace Modules\JiraIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('jira:sync-time')->everyMinute()->withoutOverlapping();
        });
    }
}


```
