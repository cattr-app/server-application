<?php

namespace App\Http\Middleware;

use App;
use App\Events\ChangeEvent;
use CatEvent;
use Closure;
use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\HttpFoundation\Response;

class RegisterModulesEvents
{
    /**
     * Add subscribers from modules for Event and Filter
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        CatEvent::listen('event.after.action.*', static function (string $eventName, array $data) {
            $eventNameParts = explode('.', $eventName);
            [$entityType, $action] = array_slice($eventNameParts, 3, 2); // Strip "event.after.action" and get the next two parts
            if (!in_array($entityType, ['tasks', 'projects', 'intervals']) || !in_array($action, ['create', 'edit', 'destroy'])) {
                return;
            }

            [$model] = $data;
            App::terminating(static function () use ($entityType, $action, $model) {
                foreach (ChangeEvent::getRelatedUserIds($model) as $userId) {
                    broadcast(new ChangeEvent($entityType, $action, $model, $userId));
                }
            });
        });

        collect(Module::allEnabled())->each(static function (\Nwidart\Modules\Module $module) {
            App::call([preg_grep("/ModuleServiceProvider$/i", $module->get('providers'))[0], 'registerEvents']);
        });

        return $next($request);
    }
}
