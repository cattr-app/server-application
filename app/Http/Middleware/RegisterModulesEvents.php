<?php

namespace App\Http\Middleware;

use App;
use App\Events\ChangeEvent;
use App\Filters\AttachmentFilter;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\TimeInterval;
use App\Observers\AttachmentObserver;
use CatEvent;
use Filter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\HttpFoundation\Response;

class RegisterModulesEvents
{
    /**
     * @param Task|Project|TimeInterval|TaskHistory $model
     */
    public static function broadcastEvent(string $entityType, string $action, $model): void
    {
        foreach (ChangeEvent::getRelatedUserIds($model) as $userId) {
            broadcast(new ChangeEvent($entityType, $action, $model, $userId));
        }
    }

    /**
     * Add subscribers from modules for Event and Filter
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TODO:
        //      [ ] move to Observers folder
        //      [ ] rewrite with laravel Event so updates that come from modules will trigger update
        CatEvent::listen('event.after.action.*', static function (string $eventName, array $data) {
            $eventNameParts = explode('.', $eventName);
            [$entityType, $action] = array_slice($eventNameParts, 3, 2); // Strip "event.after.action" and get the next two parts
            if (!in_array($entityType, ['tasks', 'projects', 'projects_members', 'intervals', 'task_comments', 'project_groups'])) {
                return;
            }

            if (!in_array($action, ['create', 'edit', 'destroy'])) {
                return;
            }

            if ($entityType === 'projects_members') {
                $entityType = 'projects';
                $projectId = $data[0];
                $model = Project::query()->find($projectId);
            } elseif ($entityType === 'task_comments') {
                $entityType = 'tasks_activities';
                $model = $data[0];
            } elseif ($entityType === 'project_groups') {
                $entityType = 'projects';
                $action = 'edit';
                /** @var ProjectGroup $group */
                $group = $data[0];
                $model = $group->projects()->get();
            } else {
                $model = $data[0];
            }

            App::terminating(static function () use ($entityType, $action, $model) {
                $items = is_array($model) || $model instanceof Collection ? $model : [$model];
                foreach ($items as $item) {
                    static::broadcastEvent($entityType, $action, $item);

                    if (in_array($entityType, ['tasks', 'projects'])) {
                        $project = match (true) {
                            $item instanceof Task => $item->project,
                            $item instanceof Project => $item,
                        };

                        static::broadcastEvent('gantt', 'updateAll', $project);
                    }
                }
            });
        });

        // CatEvent and Filter are scoped to request, subscribe on every request for it to work with laravel octane
        Filter::subscribe(AttachmentFilter::class);
        CatEvent::subscribe(AttachmentObserver::class);

        collect(Module::allEnabled())->each(static function (\Nwidart\Modules\Module $module) {
            App::call([preg_grep("/ModuleServiceProvider$/i", $module->get('providers'))[0], 'registerEvents']);
        });

        return $next($request);
    }
}
