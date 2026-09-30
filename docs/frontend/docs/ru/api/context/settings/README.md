# Настройки

__Настройки__ подразделяются на _две_ основные секции:

1. Пользовательские настройки
2. Настройки компании

В _Пользовательских настройках_ представлены настройки текущего пользователя, а именно:

1. Изменение логина, пароля, никнейма или пользовательской локали (язык, на котором будет отображаться контент приложения)
2. Добавление или изменение API ключей для различных интеграционний (Gitlab, Redmine и прочие)

В _Настройки компании_ представлены настройки, распространяющиеся на всю компанию,а именно:

1. Редактирование, добавление или удаление пользователей компании
2. Включение или отключение различных интеграций, установка их настроек, которые будут применены для всех пользователей, кто ввел API ключ
3. Создание и редактирование приоритетов у задач
4. Создание и редактирование статусов задач
5. Установка таймзоны компании, которая будет использована для различных отчетов и отображения статистики

- - -

# Арихтектура создания страниц настроек

_Контент_ предоставляемый в Пользовательских или Системных настройках _рендерится динамически_. Система рендеринга контекcта похожа на то, как создаются [CRUD](ru/api/context/crud/) или [GRID](ru/api/context/grid/) страницы.
Для формирования контента используются __секции__. <br>
__Секция__ представляет собой объект, по свойствам которого в дальнейшем создается экземпляр класса `SettingsSection`.

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

Класс `SettingsSection` имеет следующий набор свойств:

1. __path__ - используется для идентификации комопнента по URI вида _/some_section_
2. __name__ - используется в компонентах навигации, для перехода к компоненту по названию роута.
3. __meta__ - свойство экземпляра объекта vue Router, которое хранит мета информацию
4. __component__ - компонент вида _Component.vue_, который будет использован при переходе по пути роута
5. __children__ - перечень дочерних роутов
6. __accessCheck__ - _callback_-функция, по выполнению которой устанавливается доступность секции пользователю при обращении
7. __section__ - в данном свойстве формируется объект, который сохраняется в Vuex и используется компонентом для отображения данных
8. __scope__ - поле, используемое для идентификации к какому типу настроек относится секция (пользовательская, системная или иная)

Подробнее о первых четырёх параметрах можно прочитать в разделе [Маршрутизация](ru/api/context/routing/).

## Пример описания секции

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

В __секции должны быть описаны__ следующие поля:

1. __accessCheck__ - _callback_-функция, которая используется для проверки доступности секции пользователю. Если данный _callback_ возвращает `false`, то секция не будет добавлена во Vuex и как следствие не будет доступна пользователю.
2. __route__ - объект, который представляет собой обычный объект Vue Router, в meta свойстве которого описываются основные настройки секции
    * __label__ - название секции, которое будет использовано в навигации страницы настроек
    * __service__ - сервис-класс, который реализует методы сохранения, удаления и получения данных для секции. Все сервис классы наследуются от абстрактного класса `SettingsService`, в котором перечислены необходимые для имплементации методы.
    * __fields__ - описание используемых полей для работы с моделями. Подробнее можно читать [тут в секции addField](ru/api/context/crud/)

## Главные роуты настроек

__Роуты секций__ добавляются в свойство _children_ у Пользовательских или Системных роутов настроек.
Перед переходом на любой роут, относящийся к настройкам, выполняется инициализация _секций_ по необходимости.

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

## Создание новой секции Пользовательских настроек (User Settings)

Для создания новой секции необходимо создать файл _<section_name>.js_ в папке __sections__, которая находится в

```text
modules
└── AmazingCat/
    ├── CoreModule/
    │   └── sections
    │       └──<section_name>.js
    │   └── services    
    │       └── <service_name>.js

```

Описать секцию необходимо также, как это приводилось в примере выше.
Помимо создания файла секции необходимо создать __сервис-класс__ в папке _services_. Его следует унаследовать от класса `SettingsService` и реализовать все необхоимые методы.

!> Даже если секция будет работать с локальным хранилищем (Local Storage браузера), методы должны иммитировать запрос. Для достижения таких целей можно использовать пример ниже.

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

Пример сервиса, выполняющего запросы к API. Данный __сервис-класс__ используется в `DynamicSettings.vue` для отображения и работы с данными, пришедшими в ответ на запрос `getItem`.

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

## Переводы для секций настроек

Переводы для секций располагаются в папаке _locales_.

```text
modules
└── AmazingCat/
    ├── CoreModule/
    │   └── sections
    │       └──company
    │           └── locales    

```

Подробнее о создании новых переводов можно прочитать в разделе [Локализация](ru/api/context/localization/).

