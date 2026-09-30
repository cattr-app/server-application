# Modules

> Work with Cattr's modules API.
- - -

# Modules Architecture

Cattr's architecture is module-based. This means that every system's component is independent and extendable. Modules can extend both Cattr's core and 3rd party modules (see Module Loader Interceptor).
Module-based architecture allows Cattr's core to be independent without any problems or conflicts. Any module can be disconncted from the core without any damage to the main app's functionality.

Cattr's renderer module lets the developer control all the app's parts. Thus, the following components are separate and independent entities:

1. Navigation panel
2. Main user interface window
3. User settings
4. System settings (by default available for root users only)

> Cattr includes all the necessary interfaces to work with these elements. Work with those can be done via the creating module's context.
- - -

# Module development

In order to start working alongside with the Cattr core, you have to create a module.
To do this, in the vendor_modules directory, you need to create a folder with the name of the module vendor, and then inside the created folder, create another folder with the name of the module being created.

```text
The directories' structure will have to look like this:
resources
└──frontend
    └──vendor_modules
        └── MyCompanyName/
            ├── MyModuleName1/
            │   └── module.init.js
            ├── MyModuleName2/
            │   └── module.init.js
            ├── MyModuleName3/
            │   └── module.init.js
            └── ...

```

> In the system the module's name is created with this template: __VendorName_ModuleName__. This has been made in order to reduce conflicts between modules with similar names.

## Module initialization

The module's basis is the entrypoint file. It's responsible for the module's initialization and main settings.
In the module's directory you'll need to create the module.init.js file. It'll be loaded by the module manager during the app's loading.

module.init.js has to **mandatory** export two entities: __init__ function and __ModuleConfig__ constant. It's described in details bellow.

module.init.js template

```javascript
import i18n from '@/i18n';
/**
 * Module configuration
 *
 * @type {{routerPrefix: string, enabled: boolean, initOrder: number, moduleName: string}}
 */
export const ModuleConfig = {
    // Whether this module should be enabled or not (affects only the local instance of the module)
    enabled: true,
    // Default router prefix. Note: this parameter will be ignored in context.addRoutes().
    // Grid and Crud entities will automatically have this prefix
    routerPrefix: 'settings',
    // Load order for this module.
    // Note: this only affects the execution order of the init() function.
    // module.init.js will be loaded in alphabetical order of module names
    loadOrder: 11,
    // Node module name
    moduleName: 'AmazingCat_JiraIntegration',
};

/**
 * Default module initialization function, executed when the module is initialized.
 * Context — the current instance of the module
 * Router — the current state of Vue Router
 *
 * This function MUST ALWAYS return the context
 *
 * @param context: Module
 * @param router: Router
 * @returns {Module}
 */
export function init(context, router) {
    const requireSection = require.context('.', true, /^(?!.*(service|module)).*\.js$/);
    const sections = requireSection
        .keys()
        .map(fn => requireSection(fn).default)
        .map(section => {
            if (typeof section === 'function') { 
                return section(context, router);
            }
            return section;
        });

    sections.forEach(section => {
        if (section.hasOwnProperty('scope') && section.scope === 'company') {
            context.addCompanySection(section);
        } else {
            context.addSettingsSection(section);
        }
    });
    // moduleLoader initializes the module by calling its init function
    context.addField('company', 'general', {
        label: 'settings.jira.label',
        key: 'jira_enabled',
        group: 'integrations',
        // ItemView adds this component, and in the template, all components are iterated and rendered
        render(h, data) {
            if (typeof data.currentValue === 'object') {
                'jira_enabled' in data.companyData
                    ? data.inputHandler(data.companyData.jira_enabled)
                    : (data.currentValue = '0');

                this.inputHandler(data.currentValue);
            }
            return h(
                'at-select',
                {
                    props: {
                        value: data.currentValue.toString(),
                    },
                    class: {
                        'with-margin': true,
                    },
                    on: {
                        'on-change': value => {
                            data.inputHandler(value);
                        },
                    },
                },
                [
                    h('at-option', {
                        props: {
                            value: '0',
                            label: i18n.t('control.disable'),
                        },
                    }),
                    h('at-option', {
                        props: {
                            value: '1',
                            label: i18n.t('control.enable'),
                        },
                    }),
                ],
            );
        },
    });
    // Adding localization data
    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
```

### ModuleConfig

ModuleConfig const is the exported object, containing 4 properties:

1. __enabled__ - property that lets the tracker to be enabled by default. Can be overridden by administrator in app/etc/modules.config.json . Works only when the module is installed locally, instead of node-package.
2. __routerPrefix__ - property that defines routes' base prefix for GRID and CRUD pages. Keep in mind that this property doesn't have any affect on the context addRoutes method.
3. __initOrder__ - property that works as a init order number. All the modules are initialized in line, the lesser this number is, the earlier the module is going to be initialized. By default its value is 0. Keep in mind that this property only affects the module order initialization line. The entrypoint files are loaded alphabetically.
4. __moduleName__ - property that sets the module's name. Only affects modules that are installed via package manager.

### Initialization function "init"

The init function __always__ has to always be exported from the module's entrypoint file. It describes the module's behavior. The function's arguments are two params:  __context__ and __router__.

?> The init function has to always return the context that's passed to it!

__context__ is the main API Cattr interaction interface. Any interactions with Cattr core has to be going through that context only.
In other words, context is the entity that presents the module created. More details on core interractiovs via context are described in Context section.

__router__ is the Vue Router's instance. If for any reason you need to add routes bypassing the module architecrue's limitations, you can use router entity.

!> Cattr is built on VueJS framework. Even though the app's architecture doesn't give developer any kind of development limitations, it's highly recommended NOT TO interact with API VueJS directly, bypassing Cattr's core (interractions with Vue Router implemented out of the module's context bypass any Cattr core's limitations), because this can lead to the app's unexpected behavior in the future, and highly complicates the module's support by any other teams or developers.

!> __The ability to interact with the Vue Router directly from the module's init function, doesn't gurantee the app's stable work after all the actions with the router.__
