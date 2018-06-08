import {Injectable} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {AllowedAction} from '../../models/allowed-action.model';
import {Item} from '../../models/item.model';

type callbackFunc = (actions: AllowedAction) => void;

@Injectable()
export class AllowedActionsService {

    allowedActions: AllowedAction[] = [];
    callbacks: callbackFunc[] = [];

    constructor(protected api: ApiService) {
    }

    getApiPath() {
        return 'roles';
    }

    getItems(callback, id ?: number) {
        const itemsArray: Item[] = [];
        const roleId: any = {'id': id ? id : this.api.getUser().role_id};

        return this.api.send(
            this.getApiPath() + '/allowed-rules',
            roleId,
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(this.convertFromApi(itemFromApi));
                });

                callback(itemsArray);
            });
    }

    convertFromApi(itemFromApi) {
        return new AllowedAction(itemFromApi);
    }

    subscribeOnUpdate(callback: callbackFunc) {
        this.callbacks.push(callback);
    }

    getActions(): AllowedAction[] {
        return this.allowedActions;
    }

    // is user can do this actions
    can(action: string): boolean {
        const re  = new RegExp('([a-zA-Z\-\_0-9]+)\/([a-zA-Z\-\_0-9]+)');
        const matches = re.exec(action);

        if (matches === null) {
            return true;
        }

        if (!this.allowedActions.length) {
            return false;
        }

        const object = matches[1];
        const  objAction = matches[2];

        for (const allowedAction of this.allowedActions) {

            if (allowedAction.object === object &&
                allowedAction.action === objAction) {
                return true;
            } else if (allowedAction.object === object &&
                       allowedAction.action === 'full_access' ) {
                return true;
            }
        }
        return false;
    }

    updateAllowedList() {
        this.getItems(this.setupAllowedList.bind(this));
    }

    setupAllowedList(items) {
        this.allowedActions = items;

        for (const callback of this.callbacks) {
            callback(items);
        }
    }

}
