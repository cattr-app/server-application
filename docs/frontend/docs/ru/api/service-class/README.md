# Сервис-классы

> Взаимодействие с REST API backend-составляющей Cattr
---

__Сервис-классы__ - это сущности, созданные для того, чтобы стандартизировать формат взаимодействия с REST API backend-составляющей Cattr.

## Типы сервис-классов

Сервис классы делятся на два типа - Storage Service и Resource Service.

### Storage Service

__Storage Service__ - сервис-класс, используемый для взаимодействия с модулем __Vuex Storage__ и API одновременно. В качестве единственного аргумента в конструктор принимает контекст текущего модуля Vuex (который, в свою очередь, передается в качестве первого параметра в action-методы). Сервисы хранилища позволяют в удобной и стандартизированной форме использовать их в качестве маппера между REST API и Vuex хранилищем.

Пример `ApiService` (`@/service/api`):

```javascript
import StoreService from './storeService';
import axios from 'axios';

export default class ApiService extends StoreService {
    storeNs = 'user';

    constructor(context) {
        super(context);

        axios.interceptors.response.use(
            response => response,
            error => {
                if (error.hasOwnProperty('response')) {
                    if (error.response.hasOwnProperty('status')) {
                        const { status } = error.response;
                        if (status === 401) {
                            if (this.isLoggedIn()) {
                                this.context.dispatch('forceUserExit', error.response.data.message);
                            }
                        }
                    }
                }
                return Promise.reject(error);
            }
        );

    }

    token() {
        return this.context.getters['token'];
    }

    checkApiAuth() {
        return axios.get('/auth/me').then(({ data }) => {
            const { user } = data;

            this.context.dispatch('setLoggedInStatus', true);
            this.context.dispatch('setUser', user);

            return Promise.resolve();
        }).catch(() => {
            localStorage.removeItem('access_token');
            this.context.dispatch('forceUserExit');

            return Promise.reject();
        });
    }

    setUserData(user) {
        this.context.dispatch('setUser', user);
    }

    setUserToken(token) {
        if (token) {
            localStorage.setItem('access_token', token);
        } else {
            localStorage.removeItem('access_token');
        }

        this.context.dispatch('setToken', token);

        axios.defaults.headers['Authorization'] = `Bearer ${token}`;
    }

    setLoggedInStatus(status = true) {
        this.context.dispatch('setLoggedInStatus', status);
    }

    isLoggedIn() {
        return this.context.getters.loggedIn;
    }

    attemptLogin(credentials) {
        return axios.post('/auth/login', credentials).then(({ data }) => {
            this.setUserToken(data.access_token);
            this.setUserData(data.user);
            this.setLoggedInStatus();

            return Promise.resolve(data);
        }).catch(response => {
            return Promise.reject(response);
        });
    }

    logout() {
        return axios.post('/auth/logout').then(() => {
            this.context.dispatch('forceUserExit');
        });
    }

    async getAllowedRules() {
        const { data } = await axios.get('/roles/allowed-rules?with_project_roles=true');

        this.context.dispatch('setAllowedRules', data);

        return data;
    }

    async getCompanyData() {
        const { data } = await axios.get('/companymanagement/getData');

        this.context.dispatch('setCompanyData', data);

        return data;
    }
}

```

!> __Store Service класссы обязательно должны наследоваться от класса `StoreService`__. У конечного класса __обязательно__ должно быть установлено значение свойства `storeNs`, соответствующее пространству имен вашего Vuex модуля (подробнее о Vuex модулях см. в разделе [Vuex Storage](ru/api/context/vuex/))

Чтобы загрузить сервис-класс внутрь Vuex, выполните слудующие действия

1. Создайте свойство `service` внутри `state` модуля Vuex.
2. Создайте геттер для получения сервиса из состояния (`state.service`)
3. Создайте мутатор `setService`, с помощью которого вы установите состояние `state.service`. Обратите внимание, что внутри состояния модуля `service` должен быть именно экземпляром вашего сервиса, а не ссылкой на класс.
4. Внутри метода init модуля Vuex установите Storage Service для текущего модуля:

```javascript
init(ctx) {
    ctx.commit('setService', new MyStorageService(ctx));
},
```

### Resource Service

__Resource Service__ - ресурс-классы, используемые как связующее звено между REST API Cattr и frontend-приложением.

В качестве единственного параметра конструктора класс `ResourceService` принимает `idParam` - ссылка на свойство, которое обозначает id полученного объекта. По-умолчанию данное свойство равно `id`.

Ресурс-классы обязательно должны имплементировать следующие методы:

1. `getItemRequestUri(id)` - результатом выполенения данного метода должна быть __строка__, по которой возможно получение данных об __одной__ сущности по ее id; По умолчанию выбрасывает ошибку;
2. `getAll()` - метод, при помощи которого возможно получение __коллекции__ ___всех___ сущностей.
3. `getItem(id)` - метод, при помощи которого возможно получение __одной__ сущности по ее id.

Так же, ресурс-классы могут (но не должны) имплементировать следующие методы:

1. `save(data, isNew = false)` - метод, описывающий сохранение данных на backend.
    * `data` - объект или массив объектов с сохраняемыми данными
    * `isNew` - boolean-флаг, устанавливающий, является ли данная сущность только что созданной (например, при сохранении с CRUD-страницы __new__) или нет (например, была отредактирована, но не создана). По-умолчанию `false`
2. `deleteItem(id)` - метод, описывающий __удаление__ сущности по ее id

!> Обратите внимание, что методы `getAll`, `getItem`, `deleteItem` и `save` __всегда должны возвращать `Promise`__

---

Пример ресурс-класса `TasksService` (`@/service/resource/tasksService`)

```javascript
import ResourceService from '@/service/resource/resouceService';
import axios from 'axios';
import {serialize} from "../../utils/url";

export default class TasksService extends ResourceService {

    /**
     * @returns {Promise<AxiosResponse<T>>}
     */
    getAll() {
        return axios.get('tasks/list');
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     * @param filters
     */
    getItem(id, filters = {}) {
        return axios.get(this.getItemRequestUri(id) + '&'+ serialize(filters));
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     */
    getItemRequestUri(id) {
        return `tasks/show?${serialize({id})}`;
    }

    /**
     * @param userID
     * @returns {Promise<AxiosResponse<T>>}
     */
    getDashboardTasks(userID) {
        return axios.get(`tasks/dashboard?${serialize({ user_id: userID, with: ['project'] })}`);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param filters
     * @param config
     */
    getWithFilters(filters, config = {}) {
        return axios.post('tasks/list', filters, config);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     */
    deleteItem(id) {
        return axios.post('tasks/remove', { id });
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     * @param isNew
     */
    save(data, isNew = false) {
        return axios.post(`tasks/${isNew ? 'create' : 'edit'}`, data);
    }
}

```
