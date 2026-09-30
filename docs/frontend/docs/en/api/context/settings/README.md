# Context

> Working with Cattr API using module
- - -

__context__ is a main interface for interaction with the Cattr API. Any interactions with Cattr core should be done only through the context. In simple words, __context__ is the entity, that represents the module being created. For more details on methods of interacting with the core through the context, refer to the subsections.

- - -

# Main features of the Cattr core modules API

1. [Creating CRUD pages](en/api/context/crud/)
2. [Creating GRID pages](en/api/context/grid/)
3. [Adding localization](en/api/context/localization/)
4. [Adding/editing routes](en/api/context/routing/)
5. [Adding/editing navbar entries](en/api/context/navbar/)
6. [Adding/editing user and system settings](en/api/context/settings/)

# Settings

__Settings__ are divided into _two_ main sections:

1. User Settings
2. Company Settings

In the _User Settings_, you can find settings for the current user, specifically:

1. Changing the username, password, nickname, or user locale (the language in which the application content will be displayed)
2. Adding or modifying API keys for various integrations (GitLab, Redmine, and others)

In the _Company Settings_  settings that apply to the entire company are presented, including:

1. Editing, adding, or deleting company users
2. Enabling or disabling various integrations and configuring settings that will apply to all users who have entered an API key
3. Creating and editing task priorities
4. Creating and editing task statuses
5. Setting the company's timezone, which will be used for reports and statistics display

- - -

# Architecture of Creating Settings Pages

The _content_ provided in User or Company Settings is _rendered dynamically_. The context rendering system is similar to how [CRUD](ru/api/context/crud/) or [GRID](ru/api/context/grid/) pages are created.
To generate content, __sections__ are used. <br>
A __section__ is an object whose properties are used to create an instance of the  `SettingsSection` class .

```javascript
/**
 * Section class - create new section with provided params
 *
 * @param path string - section route path
 * @param name string - section route name
 * @param meta - section route meta with described fields, service etc
 * @param accessCheck - function to check if user has access to work with section content
 */
export default class SettingsSection {

    path = '';
    name = '';
    meta = {};
    component = null;
    children = [];
    accessCheck = null;
    section = {};
    scope = 'settings';

    nameDelimiter = '.';
    pathDelimiter = '/';

    constructor(path, name, meta, accessCheck = () => true, scope = 'settings', component = null, children = []) {
        this.path = this.pathDelimiter + scope + this.pathDelimiter + path;
        this.name = scope + this.nameDelimiter + name;
        this.meta = meta;
        this.component = component || (() => import( /* webpackChunkName: "settings" */ '@/views/Settings/DynamicSettings.vue'));
        this.children = children;
        this.accessCheck = accessCheck;
        this.scope = scope;

        this.section = {
            path: this.path,
            name: this.name,
            meta: this.meta,
            component: this.component,
            children: this.children,
            accessCheck: this.accessCheck,
            scope: this.scope,
        }
    }

    /**
     * Init new section in store
     * @returns {Promise<void>}
     */
    async initSection() {
        await Store.dispatch('settings/setSettingSection', this.section);
    }

    /**
     * Get section route, used to create settings child route
     * @returns {{path: string, component: null, meta, name: string}}
     */
    getRoute() {
        return {
            path: this.path,
            name: this.name,
            meta: this.meta,
            component: this.component,
            children: this.children,
        };
    }
}
```

The `SettingsSection` class has the following properties:

1. __path__ - used to identify the component by URI in the format /some_section
2. __name__ - used in navigation components to navigate to the route by name.
3. __meta__ - a property of the vue Router object that stores meta information
4. __component__ - a component like Component.vue that will be used when navigating to the route's path
5. __children__ - a list of child routes
6. __accessCheck__ - a _callback_-function that determines whether the user has access to the section content
7. __section__ - a property where an object is created and saved in Vuex for use by the component to display data
8. __scope__ - a field used to identify the type of settings the section belongs to (user, company, or others)

For more details about the first four parameters, see the section  [ Routing](ru/api/context/routing/).

## Example of Section Description

```javascript
export default {

    // Check if this section can be rendered and accessed, this param IS OPTIONAL (true by default)
    // NOTICE: this route will not be added to VueRouter AT ALL if this check fails
    // MUST be a function that returns a boolean
    accessCheck: async () => {
        let companyData = Store.getters['user/companyData'];

        if (Object.keys(companyData).length && companyData.hasOwnProperty('gitlab_enabled')) {
            return companyData.gitlab_enabled === 1;
        }

        return (await axios.get(`companymanagement/getData`)).data.gitlab_enabled === 1;
    },

    route: {
        // After processing this route will be named as 'settings.exampleSection'
        name: 'gitlab',

        // After processing this route can be accessed via URL 'settings/example'
        path: 'gitlab',

        meta: {
            // After render, this section will be labeled as 'Example Section'
            label: 'Gitlab Integration',

            // Service class to gather the data from API, should be an instance of Resource class
            service: new GitlabService(),

            // Renderable fields array
            fields: [
                {
                    label: 'Gitlab API Key',
                    key: 'apikey',
                    fieldOptions: {
                        type: 'text',
                        fckAutocomplete: false // Disable autocomplete for field
                    }
                },
            ]
        },
    }
};
```

In a __section__ , the following fields must be described:

1. __accessCheck__ - _callback_-function used to check the availability of the section for the user. If this _callback_ returns `false`, the section will not be added to Vuex and, as a result, will not be accessible to the user..
2. __route__ - an object representing a standard Vue Router object, with the main settings for the section described in the meta property:
    * __label__ - the name of the section to be used in the navigation of the settings page
    * __service__ - a service class that implements methods for saving, deleting, and retrieving data for the section. All service classes inherit from the abstract `SettingsService` class, which lists the required methods for implementation.
    * __fields__ - a description of the fields used to work with models. Read more [here in the addField section.](ru/api/context/crud/)

## Main Settings Routes

__Section routes__ дare added to the _children_ property of the User or Company settings routes.
Before navigating to any route related to settings, _sections_ are initialized if necessary.

```javascript
const settings = [
        {
            path: '/company',
            name: 'company',
            component: () => import(/* webpackChunkName: "company" */ '../views/Settings/CompanySettings.vue'),
            meta: {
                auth: true
            },
            beforeEnter: initCompanySections,
            children: coreModule.moduleInstance.getCompanySectionsRoutes(),
        },
        {
            path: '/settings',
            name: 'settings',
            component: () => import(/* webpackChunkName: "settings" */ '../views/Settings/Settings.vue'),
            meta: {
                auth: true
            },
            beforeEnter: initSettingsSections,
            children: coreModule.moduleInstance.getSettingSectionsRoutes(),
        },
    ];
```

## Creating a New User Settings Section

To create a new section, create a file named _<section_name>.js_ in the __sections__, folder located in:

```text
modules
└── AmazingCat/
    ├── CoreModule/
    │   └── sections
    │       └──<section_name>.js
    │   └── services    
    │       └── <service_name>.js

```

Describe the section as shown in the example above.
In addition to creating the section file, you need to create a __service class__  in the _services_ folder. It should inherit from the `SettingsService` class and implement all necessary methods.

!> Even if the section will work with local storage (browser's Local Storage), the methods should simulate a request. For such purposes, you can use the example below.

```javascript
/**
 * Section service class.
 * Used to fetch data from api for inside DynamicSettings.vue
 * Data is stored inside store -> settings -> sections -> data
 */
export default class GeneralTabService extends SettingsService {

    /**
     * Mocked Promise used to make service functionality same as others
     * Checking localStorage for locale if it`s not set - set english as default one
     * @returns {data}
     */
    getItem() {
        return Promise.resolve().then(() => {
            return {
                data: {
                    language: localStorage.getItem('language') ? localStorage.getItem('language') : 'en'
                }
            };
        });
    }

    /**
     * Mocked Promise used to make service functionality same as others
     * Set locale to localStorage
     *
     * @param data
     * @returns {Promise<void>}
     */
    save(data) {
        return Promise.resolve().then(() => {
            localStorage.setItem('language', data.language);
            i18n.locale = data.language;
        });
    }
}
```

An example of a service performing API requests. This _service class_ is used in `DynamicSettings.vue` to display and work with data returned in response to the `getItem` request..

```javascript
/**
 * Section service class.
 * Used to fetch data from api for inside DynamicSettings.vue
 * Data is stored inside store -> settings -> sections -> data
 */
export default class CompanyService extends SettingsService{

    /**
     * API endpoint URL
     * @returns string
     */
    getItemRequestUri() {
        return `companymanagement/getData`;
    }

    /**
     * Fetch item data from api endpoint
     * @returns {data}
     */
    getItem() {
        return axios.get(this.getItemRequestUri());
    }

    /**
     * Save item data
     * @param data
     * @returns {Promise<void>}
     */
    save(data) {
        return axios.post('companymanagement/save', data);
    }
}
``` 

## Translations for Settings Sections

Translations for sections are located in the _locales_ folder.

```text
modules
└── AmazingCat/
    ├── CoreModule/
    │   └── sections
    │       └──company
    │           └── locales    

```

For more details on creating new translations, see the section [Localization](ru/api/context/localization/).

