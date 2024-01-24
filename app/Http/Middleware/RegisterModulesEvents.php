<?php

namespace App\Http\Middleware;

use App;
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
        collect(Module::allEnabled())->each(function (\Nwidart\Modules\Module $module) {
            App::call([preg_grep("/ModuleServiceProvider$/i", $module->get('providers'))[0], 'registerEvents']);
        });
        return $next($request);
    }
}
