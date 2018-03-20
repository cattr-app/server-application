import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Rule} from "../../models/rule.model";
import {ItemsService} from "../items.service";

@Injectable()
export class RulesService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'rules';
    }

    convertFromApi(itemFromApi) {
        console.log(itemFromApi);
        return new Rule(itemFromApi)
    }

}
