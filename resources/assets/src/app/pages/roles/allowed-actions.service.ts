import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {AllowedAction} from "../../models/allowed-action.model";
import {ItemsService} from "../items.service";

@Injectable()
export class AllowedActionsService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'allowed';
    }

    convertFromApi(itemFromApi) {
        console.log(itemFromApi);
        return new AllowedAction(itemFromApi)
    }

}
