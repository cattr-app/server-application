# GRID

> GRID Pages Creation

- - -
The pages let the user to interact with entities received from backend. The page's data can be formed based on any container implementing /list method's data.

GRID page is a separate configuration object and isn't connected to context.
Any module's content can be overridden with [intercepting the module loader](en/api/module-loader-interceptor/). To do that you'll need to subscribe to the module initialization's action (ModuleLoader.on ('Module_Name', (module) => {} ) and change its router's meta property's containment, thus adding/changing/removing the displayed fields and its renderer's functions (you can learn more about it in the rednder () function's description).

The data for GRID comes from the `getAll`'s service-class method (See [Service classes](en/api/service-class/)) for details.

The page you get is the Vue Router's config object, this page's containments config is stored in the config object's meta property. See more on Vue Router config in [Routing](en/api/context/routing/) section.

- - -

# GRID creation. createGrid method.

GRID page is described by a separate entity, in comparison with all the other elements that interact with context, is Vue Router configuration.

To get the GRID's description interface, call the __context__'s `createGrid` method.

```javascript
/**
 * Create GRID instance, which can be expor ted to RouterConfig
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

1. __label__ - GRID page's shown name. Keep in mind that Vue will authomatically try to translate this field's name if there's an according translation for it. More details available in Localization section.
2. __id__ - is the page's ID
3. __serviceClass__ - [service class](en/api/service-class/), that works with getting, mapping and saving the data
4. __gridData__ - adds ability to control the Query Builder on backend, and also to extend the Grid class' functionality and fill in the necessary properties. It always has to be object. Not required param. The object's available properties are:
    * __with__ - array, describes connected eloquent-relations to the required entity's model (relation names)
    * __withCount__ - array, describes the amount of entities connected to the required model with the relation names sent to this param.
5. __gridRouterPath__ - router's default prefix for this page.

`createGrid` returns Grid class' instance.

After you set up the GRID, its config has to be sent to the router. To do this, use the `getRouterConfig` method, that returns the router config object.

```javascript
// Example of Grid Object creation
const grid = context.createGrid('tasks.grid-title', 'tasks', TasksService, {
    with: 'priority, project, user',
    filters: {
        filterName: 'filter.task',
        referenceKey: 'task_name'
    }   
  }
);
```

To load the page info to the module, send the object from the `getRouterConfig` to the __context__'s `addRoutes` method (more info can be found here [Routing](en/api/context/routing/)).

context.addRoutes (grid.getRouterConfig ());

Generated pages settings
GRID interface lets you change the page's different functions settings.

## `addColumn`

The main page interraction method is `addColumn`.

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

The `addColumn`'s argument is __array__ containing objects -- entities that describe columns displayed on the page. To describe the object's fields, you can use the following properties (* are required params):

1. __key__* - field's key, has to match the key from the data received. Module uses it to determine which data should be matching to which field.
2. __title__ - displayed field's name. Keep in mind that Keep in mind that Vue will authomatically try to translate this field's name if there's an according translation for it. More details available in Localization section.
3. __render__ -a parameter describing a custom column (see more details in the [Custom Fields](ru/api/context/crud/custom-fields/)) section). It is a function that takes two parameters: the $createElement  instance and an object containing input control parameters.
   __render__ takes two arguments: h: The $createElement instance , object: Includes the following properties:
    * __column__ - data of the current column.
    * __index__ - current index.
    * __item__ - data of the current cell.

`addColumn` config example:

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

The page's final fields configuration is stored in `meta` router configuration object, which returns the `grid`'s `getRouterConfig` object.

## `addFilter`

`addFilter` method lets you add different fields for the page's content filtering. Thus, you can filter tasks by their names. The default method for entering is input with the text type.

```javascript
grid.addFilter([
    {
        filterName: 'filter.task',
        referenceKey: 'task_name'
    }
]);
```

The filter object uses 2 properties:

1. __filterName__ - filter's name, that will be displayed near the according input
2. __referenceKey__ - model's key you'll need to use for the search in Laravel.

!> Keep in mind that the model which is also the key in the database will be used for filtering. During filtration there'll be an SQL-query created, which will look like this:

```sql
select * from table where referenceKey like "%frontend_input_data%";
```

## `addPageControls`

`addPageControls` method lets you control the page control elements (e.g. "Create" button). The HTML button is the default frontend controller.

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

The `addPageControls` method's `config` param should receive the array of objects, which describe the particular page's frontend controller. The description object contains these properties:

1. __label__ - displayed controller's name. Keep in mind that Vue will authomatically try to translate this field's name if there's an according translation for it. More details available in Localization section.
2. __type__ - button's frontend type. More information about it is available on the UI-kit Cattr uses' documentation page. Available values for this field are (see bellow on how does it look like visually):
    * primary
    * success
    * error
    * warning
    * info
      <img src="/assets/images/buttons.png">

3. __icon__ - icon displayed before the label field's value. You can learn more about available icons [here](https://at-ui.github.io/at-ui/#/en/docs/icon)
4. __onClick__ - property that describes the callback-function that handles the button click event. The first function's argument is the current __GridView.vue__ (_this_, with the available Vue Router Vuex inside it)'s context, the second one is the current entity (record)'s fields' values.
5. __renderCondition__ - _callback-function_, that should return __boolean__ value. If it's false, then frontend-controller (button) won't be displayed to user. This property's used to determine the possibility of displaying the controller to the user. This function's only argument is the current __GridView.vue__ (_this_, with the available Vue Router Vuex inside it)'s context. _Non-required param_, its default value is __true__.

`addPageControls` usage example

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

## addToMetaProperties. Controlling the Vue Router's config metadata

If you need to change the metadata in the Vue Router's final config directly (which returns the `getRouteConfig` method), you can use the `addToMetaProperties` method in the `Grid` class' instance.

?> Keep in mind that each page has its own config. Changing one page's meta-config doesn't affect the other page's one.

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

The available method's params (all of those are required):

1. __property__ - object property's name. If you need to change or add the nested property, you'll need to separate them with a comma. Thus, to change the `attribute` property's value, which is subsidary for `parentAttribute` property, you'll need to pass the __"parentAttribute.attribute"__ line as this param's value.
2. __data__ - the containment you need to add to the property's containments
3. __routerConfig__ - the page's current routerConfig. Can be gotten from the page's instance with using the `getRouterConfig` method.

## Page routes guard

To limit some users' groups access to the created pages, you can use [Routes guard](en/api/context/routing/guard/).

To protect the created pages with using the `addToMetaProperties` method, you'll need to add the value to the `permissions` meta-params. The value should be the rule's name the user should have access to. If thew user doesn't have the rule necessary, the page access will be denied.

More info on that is available in the backend documentation.
- - -

`addAction`
GRID is the functional page for working with the data. From this page you can gain access to viewing, editing, creating or removing data. To do that, use the `addAction` method that adds the buttons to the Actions column.

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

addAction method receives the array of objects with the following properties:

1. __title__ - displayed controller's name. Keep in mind that Vue will authomatically try to translate this field's name if there's an according translation for it. More details available in Localization section.
2. __actionType__ - button's frontend type. More information about it is available on the UI-kit Cattr uses' documentation page. Available values for this field are (see bellow on how does it look like visually):
    * primary
    * success
    * error
    * warning
    * info
3. __icon__ - icon displayed before the label field's value. You can learn more about available icons [here](https://at-ui.github.io/at-ui/#/en/docs/icon)
4. __onClick__ - property that describes the callback-function that handles the button click event. The first function's argument is the current __GridView.vue__ (_this_, with the available Vue Router Vuex inside it)'s context, the second one is the current entity (record)'s fields' values.
5. __renderCondition__ - _callback-function_, that should return __boolean__ value. If it's false, then frontend-controller (button) won't be displayed to user. This property's used to determine the possibility of displaying the controller to the user. This function's only argument is the current __GridView.vue__ (_this_, with the available Vue Router Vuex inside it)'s context. _Non-required param_, its default value is __true__.

## getRouterConfig. Getting the Vue Router's config and adding the configuration to the context

The `getRouterConfig` method should be called from the GRID's end instance (which returns the `createGrid` method). After all the actions around it and setting up all the unique page types, the method will return the array of objects that are Vue Router config objects (more info available [here](https://router.vuejs.org/)).

```javascript
const gridRouterConfig = grid.getRouterConfig(); // Array<Object>
```

To add the final configuration into the module, pass the getRouterConfig method's result to your context's addRoute method.

```javascript
context.addRoute(gridRouterConfig); // Add the routes' config to the module
```

?> Once you add the routers configuration to the context, the created page will be able via URI like `myapp.com/Module_Router_Prefix/Grid_Router_Prefix`
<br>

Module_Router_Prefix - router's default prefix, taken from the `ModuleConfig`<br>

Grid_Router_Prefix - page prefix that's set with the `createGrid`'s `defaultPrefix`param<br>​
