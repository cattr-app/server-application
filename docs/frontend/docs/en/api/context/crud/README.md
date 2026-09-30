# CRUD

> CRUD page creation

- - -

CRUD pages lets user interact with entities received from the backend.

CRUD page is the separate config object and isn't linked to the context. Any module's content can be overridden with [intercepting the module loader](en/api/module-loader-interceptor/) It's possible to do that with changing the according configuration object (the `meta`) property in the interceptor instance's array property.

Any module's content can be overridden with [intercepting the module loader](en/api/module-loader-interceptor/). To do that you'll need to subscribe to the module initialization's action (ModuleLoader.on ('Module_Name', (module) => {} ) and change its router's meta property's containment, thus adding/changing/removing the displayed fields and its renderer's functions (you can learn more about it in the rednder () function's description).

__CRUD__ includes 3 kinds of pages:

1. View - page that __displays__ (but doesn't allow changes) info about entity
2. Edit - page that receives entity's into to provide the ability to change and save it
3. New - page that provides the web form to create and save the entity

CRUD data is received via serivce-class' `getItem` method, and saved via `save` one (more info on that is available in the [Service classes](en/api/service-class/) section).

The final page is the Vue Router configuration object, the config for the page's containment itself is located in the configuration object's meta property. More info on Vue Router config is available on the [Routing](en/api/context/routing/) section.

- - -

# CRUD creation. createCrud method

CRUD page is described by the separate entity, and, in contrast to all the other context interraction elements, is part of the Vue Router config.

To get the CRUD's description interface, call the __context__'s `createCrud` method.

```javascript
/**
 * Create CRUD instance, which can be exported to RouterConfig
 *
 * @param label
 * @param id
 * @param serviceClass
 * @param filters
 * @param defaultPrefix
 * @param pages
 * @returns {Crud}
 */
createCrud(label, id, serviceClass, filters, defaultPrefix = '', pages = { edit: true, view: true, new: true }) {
    const crud = new Crud(label, id, serviceClass, filters, this, defaultPrefix, pages);
    return crud;
}
```

1. __label__ - CRUD page's displayed name. Keep in mind that Vue will authomatically try to translate this field's name if there's an according translation for it. More details available in [Localization](en/api/context/localization/) section.
2. __id__ - The page identification key is used for creating routes and identification.
3. __serviceClass__ - [service class](en/api/service-class/), that works with getting, mapping and saving the data
4. __filters__ - adds ability to control the Query Builder on backend, and also to extend the Grid class' functionality and fill in the necessary properties. It always has to be object. Not required param. The object's available properties are:
    * __with__ - array, describes connected eloquent-relations to the required entity's model (relation names)
    * __withCount__ - array, describes the amount of entities connected to the required model with the relation names sent to this param.
5. __defaultPrefix__ - router's default prefix for this page. Non-required param.
6. __pages__ - The pages that will be available when creating a CRUD instance. It is an object. By default, all three are included: edit, view, new
   `createCrud` returns Crud class instance.

After creating a CRUD instance, its configuration needs to be passed to the routes. The `getRouterConfig` method is used for this, which returns a configuration object for Vue Router.

```javascript
const crud = context.createCrud('Sample Page', 'sample-page', SomeServiceClass);
```

To load the page data into the module, pass the object from `getRouterConfig` to the __context__'s `addRoutes` method (more info available here: [Routing](en/api/context/routing/)).

```javascript
context.addRoutes(crud.getRouterConfig());
```

- - -

# Generated pages settings

CRUD instance returned by `createCrud` method gives access to New Edit and View representations addons with the methods mentioned bellow.

Every page is presented by a separate interface, access to which can be gained with the CRUD instance's according property.

```javascript
const view = crud.view; // view-page interface
const edit = crud.edit; // edit-page interface
const new = crud.new; // new-page interface
```

?>Pages new and edit should be described via Vue component EditView, the view pages shpuld be descrived by Vue component ItemView. More information on that is available on the Advanced API section.

## `addField`

Main object to interact with the page is `addField` method.

```javascript
/**
 * @param fieldConfig
 * @returns {New}
 */
addField(fieldConfig) {
    this.addToMetaProperties('fields', fieldConfig, this.getRouterConfig());
    return this;
}
```

The `fieldConfig`'s argument is __array__of objects that describe the form's fields. To describe a field, the object contains the following properties (* are required params)

1. __key__* - field's key, has to match the key from the data received. Module uses it to determine which data should be matching to which field.
2. __label__ - displayed controller's name. Keep in mind that Vue will authomatically try to translate this field's name if there's an according translation for it. More details available in [Localization](en/api/context/localization/) section.
3. __type__ - field's frontend type. Available values for this field are (see bellow on how does it look like visually):
    * __text__ (or input)
    * __textarea__
    * __select__
    * __number__
    * __resource-select__
4. __required__ - sets the required param for the HTML field. Type: boolean. Default value: false
5. __frontendType__ - can be used only for text/input. Sets the type HTML attribute's value for the field. _Keep in mind that for number type you'll need to use the number type for the type param._
6. __options__ - array used to list the available options for select type fields. Is array of objects with two properties:
    * __label__ - displayed option's name
    * __value__ - sent option's value
7. __displayable__ - boolean, determines whether the field is displayed on the frontend. The default value is false.
8. __render__ - describes the custom field (more info is available in the [Custom field](en/api/context/crud/custom-fields/) section). __Is a function__, which accepts two params -- the [__$createElement__](https://vuejs.org/v2/guide/render-function.html) instance and the object containing the input control params
    * __inputHandler__ - input handler function, sets up as the v-on:input event handler for the according field.
    * __currentValue__ - the field's current value. If the value is empty or it wasn't set up, the property's value will be an empty object
    * __focusHandler__ - field's focus handler, you can install it as a v-on:focus handler for the field you're editing
    * __blurHandler__ - field's blur handler, you can install it as a v-on:blur handler for the field you're editing
    * __field__ - object that describes the field's configuration.

`addField` config example:

```javascript
const fieldsToFill = [
    {
        key: 'id',
        displayable: false
    },
    {
        label: 'field.name',
        key: 'name',
        type: 'text',
        placeholder: 'field.name',
        required: true
    },
    {
        label: 'field.description',
        key: 'description',
        type: 'textarea',
        required: true
    },
    {
        label: 'field.important',
        key: 'important',
        type: 'select',
        options: [
            {
                value: 0,
                label: 'control.no'
            },
            {
                value: 1,
                label: 'control.yes'
            }
        ]
    }
];

crud.new.addField(fieldsToFill);
crud.edit.addField(fieldsToFill);
```

The page's final fields configuration is stored in `meta` router configuration object, which returns the `crud`'s `getRouterConfig` object.

?> Keep in mind that different field configs can be set for different pages

## `addPageControls`

`addPageControls` method lets you control the page control elements (e.g. "Back" or "Remove buttons). The HTML button is the default frontend controller.

!> Keep in mind that the pages of Edit and New types the "Save" button is created automatically, which calls the __service class__'s save method.

```javascript
/**
 *
 * @param config
 * @returns {New}
 */
addPageControls(config) {
    this.addToMetaProperties('pageData.pageControls', config, this.getRouterConfig());
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
5. __renderCondition__ - callback-function_, that should return __boolean__ value. If it's false, then frontend-controller (button) won't be displayed to user. This property's used to determine the possibility of displaying the controller to the user. This function's only argument is the current __GridView.vue__ (_this_, with the available Vue Router Vuex inside it)'s context. _Non-required param_, its default value is __true__.

## addToMetaProperties. Controlling the Vue Router's config metadata

If you need to change the metadata in the Vue Router's final config directly (which returns the `getRouteConfig` method), you can use the `addToMetaProperties` method in the required page's instance (new, edit or view).

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

1. __property__ is object property's name. If you need to change or add the nested property, you'll need to separate them with a comma. Thus, to change the `attribute` property's value, which is subsidary for `parentAttribute` property, you'll need to pass the __"parentAttribute.attribute"__ line as this param's value.
2. __data__ is the containment you need to add to the property's containments
3. __routerConfig__ is the page's current routerConfig. Can be gotten from the page's instance with using the `getRouterConfig` method.

## Page routes guard

o limit some users' groups access to the created pages, you can use [Routes guard](en/api/context/routing/guard/).

To protect the created pages with using the `addToMetaProperties` method, you'll need to add the value to the `permissions` meta-params. The value should be the rule's name the user should have access to. If thew user doesn't have the rule necessary, the page access will be denied.

More info on that is available in the backend documentation.
- - -

# getRouterConfig. Gathering the Vue Router config and adding it to the context

The `getRouterConfig` method should be called from the GRID's end instance (which returns the `createCrud` method). After all the actions around it and setting up all the unique page types, the method will return the array of objects that are Vue Router config objects (more info available [here](https://router.vuejs.org/)).

```javascript
const crudRouterConfig = crud.getRouterConfig(); // Array<Object>
```

To add the final configuration into the module, pass the getRouterConfig method's result to your `context`'s `addRoute` method.

```javascript
context.addRoute(crudRouterConfig); // Add the routes' config to the module
```

?>Once you add the routers configuration to the context, the created page will be able via __\<Module_Router_Prefix\>/\<Crud_Router_Prefix\>__ via next URIs:<br><br>
New - /new </br>
Edit - /edit/\<id\> <br>
View - /view/\<id\> <br>
<br>
__Module_Router_Prefix__ - router's default prefix, taken from the ModuleConfig

__Crud_Router_Prefix__ - page prefix that's set with the `createCrud`'s `defaultPrefix`param <br>

__id__ is the requested entity's id. Will be compared with the __service class__'s `getIdParam` method. More info on that is available in the [Service classes](en/api/service-class/) section.
