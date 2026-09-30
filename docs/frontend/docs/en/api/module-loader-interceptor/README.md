# Module Loader Interceptor

> Work with module loader interceptor

- - -

Cattr lets developers to improve and extend the already existing modules. To have access to the 3rd party module after it's initialized, you can use __module loader interceptor__.

- - -

# ModuleLoaderInterceptor

Import the `ModuleLoaderInterceptor` to the module's __entrypoint__.

```javascript
import { ModuleLoaderInterceptor } from '@/moduleLoader';
```

ModuleLoaderInterceptor is the default `EventEmitter`'s instance from JavaScript, that has all the [emitter's default methods](https://nodejs.org/api/events.html). After the required module has been initialized, `ModuleLoaderInterceptor` will execute an event whose name corresponds to the name of the initialized module, passing the instance of the initialized module to the event. ([context](en/api/context/)).

__AmazingCat_SampleModule__'s loading interception example:

```javascript
export function init(context) {
    context.addRoute({
        path: '/report/projects',
        name: 'report.projects',
        component: () => import(/* webpackChunkName: "project-report" */ './views/ProjectReport.vue'),
        meta: {
            auth: true,
        },
    });

```

In this example, a new route will be added to the module, which will be accessible via the URI. `/report/projects`.

## The "If Module is Active" event

If the module is active, it is added to the initialization queue moduleInitQueue:

```javascript 
    if (moduleEnabled) {
        moduleInitQueue.push({
            module: md,
            order: moduleInitData.loadOrder || 999,
            moduleInitData,
            fullModuleName,
            fn,
            type: 'local',
        });
    }
```

## "All modules are loaded" event

If you need to execute some code after all the modules were loaded, including the current one, you'll need to intercept the `loaded` event. The param that's going to be sent to the event, is the Vue Router's instance with all the module's routes being added already.

```javascript
ModuleLoaderInterceptor.on('loaded', router => {
    console.log('All modules loaded successfully!');
}
```

In this example, after all the modules are loaded, you'll see the essage in the developer console:

```text
All modules loaded successfully!
```
