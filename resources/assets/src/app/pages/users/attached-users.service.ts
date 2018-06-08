import {Injectable} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {User} from '../../models/user.model';
import {Item} from '../../models/item.model';


type callbackFunc = (actions: User) => void;


@Injectable()
export class AttachedUsersService {

    attachedUsers: Item[] = [];
    callbacks: callbackFunc[] = [];

    constructor(protected api: ApiService) {
    }

    getApiPath() {
        return 'users';
    }

    subscribeOnUpdate(callback: callbackFunc) {
        this.callbacks.push(callback);
    }

    getItems(callback, id ?: number) {
        const itemsArray: Item[] = [];
        const userId: any = {'id': id ? id : this.api.getUser().id};

        return this.api.send(
            this.getApiPath() + '/relations',
            userId,
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(this.convertFromApi(itemFromApi));
                });

                callback(itemsArray);
            },
            this.errorCallback.bind(this)
        );
    }

    errorCallback(error) {
        if (error.status === 403 && error.error.reason === 'action is not allowed') {
            this.setupAttachedList([]);
        }
        console.log('attached error');
    }

    convertFromApi(itemFromApi) {
        return itemFromApi;
    }

    updateAttachedList() {
        const storageItems: any = this.api.getAttachedUsers() ? this.api.getAttachedUsers() : [];

        if (storageItems.length > 0) {
            this.setupAttachedList(storageItems);
        } else {
            this.getItems(this.setupAttachedList.bind(this));
        }
    }

    setupAttachedList(items) {
        this.attachedUsers = items;
        this.api.setAttachedUsers(items);

        for (let i = 0; i < this.callbacks.length; i++ ) {
            this.callbacks.pop()(items);
        }
    }
}
