import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Action} from "../../models/action.model";
import {ItemsService} from "../items.service";

@Injectable()
export class ActionsService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'actions';
    }

    convertFromApi(itemFromApi) {
        console.log(itemFromApi);
        return new Action(itemFromApi)
    }

}
