# Перехват инициализации модулей (Module Loader Interceptor)

> Работа с перехватчиком инициализации модулей

- - -

Cattr предоставляет разработчикам возможность дополнения и расширения уже существующих модулей. Для того, чтобы получить доступ к стороннему модулю после его инициализации, разработчик может воспользоваться __перехватчиком инициализации модулей__.

- - -

# ModuleLoaderInterceptor

В __точке входа__ создаваемого модуля импортируйте `ModuleLoaderInterceptor`

```javascript
import { ModuleLoaderInterceptor } from '@/moduleLoader';
```

ModuleLoaderInterceptor - экземпляр стандартного `EventEmitter` из JavaScript, который обладает всеми [стандартными методами эмиттера](https://nodejs.org/api/events.html). После того, как требуемый модуль был инициализирован, `ModuleLoaderInterceptor` выполнит событие, название которого соответствует названию инициализированного модуля, при этом в событие будет передан экземпляр инициализированного модуля ([контекст](ru/api/context/)).

Пример инициализации загрузки модуля:

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

В данном примере, в модуль будут добавлен новый маршрут, который будет доступ по URI `/report/projects`.

## Событие "Если модуль активен"

Если модуль активен, он добавляется в очередь инициализации moduleInitQueue:

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

## Событие "Все модули были загружены"

Если требуется выполнить определенный код только после загрузки всех модулей (включая текущий модуль), необходимо перехватить событие `loaded`. В качестве передаваемого в событие параметра будет передан экземпляр Vue Router с уже добавленными в него маршрутами из всех модулей.

```javascript
ModuleLoaderInterceptor.on('loaded', router => {
    console.log('All modules loaded successfully!');
}
```

В данном примере, после загрузки всех модулей, в консоль разработчика отобразится сообщение

```text
All modules loaded successfully!
```
