import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {User} from '../../models/user.model';
import {ItemsService} from '../items.service';

@Injectable()
export class UsersService extends ItemsService {

    getApiPath() {
        return 'users';
    }

    constructor(api: ApiService) {
        super(api);
    }

    convertFromApi(itemFromApi) {
        return new User(itemFromApi);
    }

    editItems(items, callback, errorCallback ?: null) {
        this.api.send(
            this.getApiPath() + '/bulk-edit',
            {'users': items},
            (result) => {
                callback(result);
            },
            errorCallback ? errorCallback : this.api.errorCallback.bind(this)
        );
    }

    createAssignedUsers(items, callback, errorCallback ?: null) {
        this.api.send(
            'attached-users/bulk-create',
            {'relations': items},
            (result) => {
                callback(result);
            },
            errorCallback ? errorCallback : this.api.errorCallback.bind(this)
        );
    }

    removeAssignedUsers(items, callback, errorCallback ?: null) {
        this.api.send(
            'attached-users/bulk-remove',
            {'relations': items},
            (result) => {
                callback(result);
            },
            errorCallback ? errorCallback : this.api.errorCallback.bind(this)
        );
    }
}
