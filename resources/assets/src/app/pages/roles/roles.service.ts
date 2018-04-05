import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Role} from "../../models/role.model";
import {ItemsService} from "../items.service";

@Injectable()
export class RolesService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'roles';
    }

    convertFromApi(itemFromApi) {
        console.log(itemFromApi);
        return new Role(itemFromApi)
    }

}