# Service classess

> Work with Cattr's REST API backend-part
---

__Service classess__ are entities used to standartisize the interaction with the Cattr's REST API backend part.

## Service class types

Service classes can be divided on Storage Service and Resource Service types.

### Storage Service

__Storage Service__ is a service-class,that's used for interaction with __Vuex Storage__ and API at the same time. The class' construcor receives the current module's Vuex context as a argument (which is being sent as a first param to the action-methods). The storage's services lets you use them as a mapper between REST API and Vuex storage in a convenient and standartisized form.

`ApiService` (`@/service/api`) example:

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

!> __Store Service classess has to be inherited from the `StoreService`__. The end class should __mandatory__ have the `storeNs` value to be according to the namespace of your Vuex module (more details on Vuex modules can be fount on [Vuex Storage](en/api/context/vuex/) page).

To load the service-class into Vuex, do the following:

1. Create the `service` property in the Vuex module's `state`.
2. Create the getter to be able to get the service from the state (`state.service`).
3. Create the `setService` mutator, which will let you set the `state.service` state. Keep in mind that the `service`, inside the module's state, has to be your service's instance, not the classlink.
4. Set the Storage Service for the current module inside the Vuex's init module:

```javascript
init(ctx) {
    ctx.commit('setService', new MyStorageService(ctx));
},
```

### Resource Service

__Resource Service__ are resource-classes that are used as a connecting lingk between Cattr's REST API and the frontend-app.

!>Resource-classes have to mandatory inherit the `ResourceService` (`@/service/resource/resourceService`) class.

The `ResourceService` class's constructor accepts the `idParam` as a one and only param. It's a link to the property that contains the received object's ID. By default this property has the `id` value anyways. Throws an error by default.

The mandatory methods for resource classes:

1. `getItemRequestUri(id)` - the method's execution result should be a __string__, using which you can get the data about __one__ entity based on its id;
2. `getAll()` - should return __collection__ of __all__ the entities.
3. `getItem(id)` - should return __one__ entity based on its id.

Also resource-classes can (but don't have to) implement:

1. `save(data, isNew = false)` - how to save items to the backend.
    * `data` - object or array with the saved data
    * `isNew` - boolean-flag, that stores the info, whether this entity has just been created (e.g. when saving from the CRUD-page __new__) or not. Default value is `false`
2. `deleteItem(id)` - method, that __remove__ entity by it`s id

!> Keep in mind that the `getAll`, `getItem`, `deleteItem` and `save` methods __always have to return `Promise`__

---

`TasksService` resource class example (`@/service/resource/tasksService`)

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
