# GRID

> Создание GRID страниц

- - -

Страницы позволяют конечному пользователю взаимодействовать с сущностями, полученными с бэкенда. Данные страницы могут быть сформированы на данных, полученных от любого контроллера, реализующего эндпоинт /list.

GRID страница является отдельным конфигурационным объектом и не привязывается к контексту.
Изменение содержимого страницы при [перехвате инициализации модуля](ru/api/module-loader-interceptor/) возможно при помощи изменения требуемого объекта конфигурации (свойство meta) внутри массива свойства routes экземпляра context внутри перехватчика.

Данные для GRID получаются при помощи описанного в сервис-классе метода `getAll` (подробнее см. в [Сервис классы](ru/api/service-class/)).

Итоговая страница представляет из себя объект конфигурации Vue Router, непосредственная конфигурация содержимого страницы находится внутри свойства meta объекта конфигурации. Подробнее о конфигурации Vue Router см. в разделе [Маршрутизация](ru/api/context/routing/)

- - -

# Создание GRID. Метод createGrid

GRID страница описывается отдельной сущностью и, в отличии от остальных элементов взаимодействия с контекстом, в итоге является конфигурацией Vue Router.

Чтобы получить интерфейс описания GRID, необходимо вызвать метод `createGrid` объекта __context__.

```javascript
/**
 * Create GRID instance, which can be exported to RouterConfig
 * @param label
 * @param id
 * @param serviceClass
 * @param gridData
 * @param gridRouterPath
 * @returns {Grid}
 */
createGrid(label, id, serviceClass, gridData = undefined, gridRouterPath = '') {
    const grid = new Grid(label, id, serviceClass, this, gridData, gridRouterPath);
    return grid;
}
```

1. __label__ - отображаемое название GRID страницы. Обратите внимание, что Vue автоматически попытается перевести значение данного поля, если для его содержимого будет найден сопоставимый перевод. Более подробно см. в разделе Локализация​
2. __id__ - идентификационный ключ страницы
3. __serviceClass__ - [сервис класс](ru/api/service-class/), отвечающий за получение, маппинг и сохранение данных
4. __gridData__ - добавляет возможность управления Query Builder'а на бэкэнде, а также расширять фунционал класса Grid и заполнять необходимые его свойства. Всегда должен быть объектом. Необязательный параметр. Доступные свойства объекта:
    * __with__ - массив, описывает подключаемые к модели требуемой сущности eloquent-отношения (имена отношений)
    * __withCount__ - массив, описывает количество сущностей, связанных с требуемой моделью переданными в данный параметр именами отношений.
5. __gridRouterPath__ - префикс роутера по-умолчанию для данной страницы.

`createGrid` возвращает экземпляр класса Grid.

После настройки GRID, его конфигурацию необходимо передать роутеру. Для этого воспользуйтесь методом `getRouterConfig`, который возвращает объект конфигурации роутера

```javascript
// Пример создания объекта Grid
const grid = context.createGrid('tasks.grid-title', 'tasks', TasksService, {
    with: 'priority, project, user',
    filters: {
        filterName: 'filter.task',
        referenceKey: 'task_name'
    }   
  }
);
```

Для того, чтобы загрузить данные о странице в модуль, передайте объект, полученный из `getRouterConfig` в метод `addRoutes` объекта __context__ (подробнее см. [Маршрутизация](ru/api/context/routing/))

context.addRoutes (grid.getRouterConfig ());
Управление сгенерированными страницами
Интерфейс GRID предоставляет разработчику возможность изменения и надстройки различных функций страницы.

## `addColumn`

Основной элемент взаимодействия со страницей - метод `addColumn`.

```javascript
/**
 * 
 * @returns {Grid}
 */
addColumn() {
    const arg = arguments[0];
    this.addToGridData('columns', arg);
    return this;
}
```

В качестве аргумента метод `addColumn` принимает __массив__, состоящий из объектов - сущностей, которые описывают ту или иную колонку, которая будет отображена на странице. Для описания поля в объекте доступны следующие свойства (* указаны обязательные параметры):

1. __key__* - ключ поля, должен соответствовать ключу из полученных данных. По нему модель определяет, какие данные сопоставить с каким полем.
2. __title__ - отображаемое имя поля. Обратите внимание, что при рендере Vue автоматически попытается перевести данное поле, если для него были созданы переводы. Подробнее смотрите в разделе Локализация​
3. __render__ - параметр, описывающий кастомную колонку (подробнее смотрите в разделе [Кастомные поля](ru/api/context/crud/custom-fields/))). Является функцией, которая принимает в себя два параметра - экземпляр $createElement и объект, содержащий в себе параметры контроля ввода. __render__ принимает два аргумента: первый — это h, второй — объект, включающий:
    * __column__ - данные текущего столбца.
    * __index__ - текущий индекс
    * __item__ - данные текущей ячейки.

Пример конфигурации `addColumn`:

```javascript
grid.addColumn([
    {
        title: 'field.task',
        key: 'task_name',
        render: (h, { item }) => {
            const classes = ['tasks-grid__task'];
            if (!item.active) {
                classes.push('tasks-grid__task--inactive');
            }
​
            return h('span', {
                class: classes,
                attrs: { title: item.task_name },
            }, item.task_name);
        },
    },
    {
        title: 'field.project',
        key: 'project',
        render: (h, { item }) => {
            let projectName;
            if (typeof item.project !== 'undefined' && item.project !== null) {
                projectName = item.project.name;
            } else {
                projectName = '';
            }
​
            return h('span', {
                class: 'tasks-grid__project',
                attrs: { title: projectName },
            }, projectName);
        }
    },
    {
        title: 'field.user',
        key: 'user',
        render: (h, { item }) => {
            const user = item.user;
            if (!user) {
                return null;
            }
​
            return h('div', {class: 'flex'}, [
                 h('AtTooltip', {
                     props: {
                         placement: 'top',
                         content: user.full_name
                     }
                 }, [
                    h(UserAvatar, {
                        props: {
                            user,
                            showTooltip: true
                        }
                    })
                ])
            ]);
        }
    },
]);
```

Итоговая конфигурация полей страницы хранится внутри объекта `meta` объекта конфигурации роутера, которую возвращает метод `getRouterConfig` объекта `grid`.

## `addFilter`

Метод `addFilter` позволяет добавлять различные поля для фильтрации контента страницы. Так, например, можно добавить фильтр по названию задачи. Дефолтным элементом для осуществления ввода является input с типом text.

```javascript
grid.addFilter([
    {
        filterName: 'filter.task',
        referenceKey: 'task_name'
    }
]);
```

Объект фильтра использует два свойства:
lterName__ - название фильтра, которое будет отображаться у соответствующего input-а

2. __referenceKey__ - ключ модели, по которому необходимо осуществлять поиск в Laravel.

!> Обратите внимание, что при фильтрации будет использован ключ модели, который в свою очередь является колонкой таблицы в базе данных.При фильтрации строится SQL-запрос, который в псевдокоде имеет следующий вид:

```sql
select * from table where referenceKey like "%frontend_input_data%";
```

## `addPageControls`

Метод `addPageControls` позволяет управлять элементами контроля страницы (например, кнопка "Создать". По-умолчанию frontend-контроллером является HTML-кнопка.

```javascript
/**
 * 
 * @returns {Grid}
 */
addPageControls() {
    const data = arguments[0];
    if (Array.isArray(data)) {
        data.forEach(p => {
            this.routerConfig.meta.pageControls.push(p);
        });
    } else {
        this.routerConfig.meta.pageControls.push(data);
    }
    return this;
}
```

Параметр `config` метода `addPageControls` принимает в себя массив объектов, которые описывают определенный frontend-контроллер страницы. В объекте описания доступны следующие свойства:

1. __label__ - отображаемое имя контроллера. Обратите внимание, что Vue автоматически попытается перевести значение данного поля, если для его содержимого был найден перевод. Более подробнее см. в разделе Локализация​
2. __type__ - frontend-тип кнопки. Более подробно вы можете узнать на странице документации UI-кита, который использует Cattr. Доступные значения для данного поля (визуальное отображение см. ниже)
    * primary
    * success
    * error
    * warning
    * info
      <img src="/assets/images/buttons.png">
3. __icon__ - иконка, которая будет отображена перед значением поля label. О доступных иконках можно узнать [тут](https://at-ui.github.io/at-ui/#/en/docs/icon)
4. __onClick__ - свойство, описывающее callback-функцию для обработки события нажатия на кнопку. В качестве первого аргумента данная функция принимает текущий контекст элемента __GridView.vue__ (_this_, с доступным в нем Vue Router и Vuex), в качестве второго - значение полей текущей сущности (записи).
5. __renderCondition__ - _callback-функция_, которая должна возвращать __boolean__-значение. В случае, если значение является false, то frontend-контроллер (кнопка) не будет отображена пользователю. Данное свойство используется для определения возможности отображения контроллеру пользователю. В качестве единственного аргумента функция принимает текущий контекст __GridView.vue__ (_this_, с доступным в нем Vue Router и Vuex). _Необязательный параметр_, true по-умолчанию.

Пример использования `addPageControls`.

```javascript
grid.addPageControls([
    {
        label: 'control.create',
        type: 'primary',
        icon: 'icon-edit',
        onClick: ({ $router }) => {
            $router.push({ name: crudNewRoute });
        },
        renderCondition: ({ $store }) => {
            return havePermission($store.getters['user/allowedRules'], 'tasks/create');
        }
    }
]);
```

## addToMetaProperties. Управление мета-данными конфигурации Vue Router

Если есть необходимость напрямую изменить мета-данные итоговой конфигурации Vue Router (которую, в свою очередь, возвращает метод `getRouteConfig`), вы можете воспользоваться методом `addToMetaProperties` у экземпляра класса `Grid`.

?>Обратите внимание, что каждая страница имеет свой собственный конфиг. Изменение мета-конфигурации на одном типе странице не повлияет на мета-конфигурацию другой.

```javascript
/**
 * @param property
 * @param data
 * @param routerConfig
 */
addToMetaProperties(property, data, routerConfig) {
    _.set(routerConfig.meta, property, data);
}
```

Доступные параметры метода (все из них обязательны)

1. __property__ - имя свойства объекта. Если необходимо изменить или добавить вложенное свойство, разделяйте их через запятую. Так, например, чтобы изменить значение свойства `attribute`, которое, в свою очередь, является дочерним для свойства `parentAttribute`, необходимо в качестве значения данного параметра передать строку __"parentAttribute.attribute"__
2. __data__ - содержимое, которые требуется задать для свойства property
3. __routerConfig__ - текущий routerConfig страницы. Можно получить у экземпляра страницы при помощи метода `getRouterConfig`.

## Защита маршрутов страниц

Для того, чтобы ограничить возможность некоторых групп пользователей доступа к создаваемым страницам, вы можете использовать [Защиту маршрутов](ru/api/context/routing/guard/).

Для того, чтобы защитить создаваемые страницы, при помощи метода `addToMetaProperties` задайте значение мета-параметра `permissions`. В качестве значение должно выступать имя правила, которое должно быть у пользователя. Если правила у пользователя нет, доступ к странице будет запрещен.

Более подробно о правилах доступа читайте в документации к backend-составляющей.
- - -

`addAction`
GRID - это своего рода функциональная страница по работе с данными. С этой страницы мы можем получить доступ к просмотру, редактированию, созданию или удалению данных. Для этого используется метод addAction, который добавляет кнопки в колонку Actions.

```javascript
grid.addAction([
    {
        title: 'control.view',
        icon: 'icon-eye',
        onClick: (router, { item }) => {
            router.push({ 
                name: crudViewRoute,
                params: { id: item.id } 
            });
        },
        renderCondition({ $store }) {
            return havePermission(
                $store.getters['user/allowedRules'], 
                'tasks/show'
            );
        }
    },
    {
        title: 'control.edit',
        icon: 'icon-edit',
        onClick: (router, params) => {
            router.push({ 
                name: crudEditRoute, 
                params: { id: params.item.id } 
            });
        },
        renderCondition: ({ $store }) => {
            return havePermission(
                $store.getters['user/allowedRules'], 
                'tasks/edit'
            );
        }
    },
    {
        title: 'control.delete',
        actionType: 'error',
        icon: 'icon-trash-2',
        onClick: async (router, params, context) => {
            const taskService = new TasksService();
            await taskService.deleteItem(params.item.id);
            context.tableData = context.tableData.filter(item => item.id !== params.item.id);
            context.$Notify({
                type: 'success',
                title: 'Success',
                message: 'Task deleted successfully'
            });
        },
        renderCondition: ({ $store }) => {
            return havePermission(
                $store.getters['user/allowedRules'], 
                'tasks/remove'
            );
        }
    }
]);
```

Метод addAction принимает массив объектов со следующими свойствами:

1. __title__ - отображаемое имя контроллера. Обратите внимание, что Vue автоматически попытается перевести значение данного поля, если для его содержимого был найден перевод. Более подробнее см. в разделе Локализация​
2. __actionType__ - frontend-тип кнопки. Более подробно вы можете узнать на странице документации UI-кита, который использует Cattr. Доступные значения для данного поля (визуальное отображение см. ниже)
    * primary
    * success
    * error
    * warning
    * info
3. __icon__ - иконка, которая будет отображена перед значением поля label. О доступных иконках можно узнать [тут](https://at-ui.github.io/at-ui/#/en/docs/icon)
4. __onClick__ - свойство, описывающее callback-функцию для обработки события нажатия на кнопку. В качестве первого аргумента данная функция принимает текущий контекст элемента GridView.vue (this, с доступным в нем Vue Router и Vuex), в качестве второго - значение полей текущей сущности (записи).
5. __renderCondition__ - _callback_-функция, которая должна возвращать __boolean__-значение. В случае, если значение является false, то frontend-контроллер (кнопка) не будет отображена пользователю. Данное свойство используется для определения возможности отображения контроллеру пользователю. В качестве единственного аргумента функция принимает текущий контекст GridView.vue (this, с доступным в нем Vue Router и Vuex). Необязательный параметр, true по-умолчанию.

## getRouterConfig. Получение конфигурации Vue Router и добавление конфигурации в контекст

Метод `getRouterConfig` должен быть вызван у итогового экземпляра GRID (который возвращает метод createGrid). После всех взаимодействий и настроек отдельных типов страниц, данный метод вернет массив объектов, которые представляют из себя объекты конфигурации Vue Router (подробнее см. [здесь](https://router.vuejs.org/)).

```javascript
const gridRouterConfig = grid.getRouterConfig(); // Array<Object>
```

Для того, чтобы добавить итоговую конфигурацию в модуль, необходимо передать результат выполнения метода getRouterConfig в метод addRoute вашего context.

```javascript
context.addRoute(gridRouterConfig); // Добавление конфигурации маршрутов в модуль
```

?>После добавления конфигурации маршрутов в контекст, созданная страница будет доступна по URI вида myapp.com/Module_Router_Prefix/Grid_Router_Prefix
<br>
Module_Router_Prefix - префикс роутера по-умолчанию, взятый из ModuleConfig<br>
Grid_Router_Prefix - префикс для страниц, который устанавливается параметром<br>

​
