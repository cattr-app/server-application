import {EventEmitter, Injectable, Output, OnInit} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {AllowedAction} from "../../models/allowed-action.model";
import {ItemsService} from "../items.service";

type callbackFunc = (actions: AllowedAction) => void;


@Injectable()
export class AllowedActionsService extends ItemsService {

    allowedActions: AllowedAction[] = [];
    callbacks: callbackFunc[] = [];


    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'allowed';
    }

    convertFromApi(itemFromApi) {
        return new AllowedAction(itemFromApi)
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

        if(!this.allowedActions.length) {
            return false;
        }

        const object = matches[1];
        const  objAction = matches[2];

        for (const allowedAction of this.allowedActions) {

            if (allowedAction.object == object &&
                allowedAction.action == objAction) {
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

        for (let callback of this.callbacks) {
            callback(items);
        }
    }

}
