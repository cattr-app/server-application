import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Rule} from '../../models/rule.model';
import {ItemsService} from '../items.service';
import {Item} from "../../models/item.model";

@Injectable()
export class RulesService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'rules';
    }

    convertFromApi(itemFromApi) {
        return new Rule(itemFromApi);
    }

    editItems(id, items, callback, errorCallback ?: null) {
        const data = [];

        for (const item of items) {
            data.push({
                'role_id': id,
                'object': item.object,
                'action': item.action,
                'allow': item.allow
            });
        }

        this.api.send(
            this.getApiPath() + '/bulk-edit',
            {'rules': data},
            (result) => {
                callback(result);
            },
            errorCallback ? errorCallback : this.api.errorCallback.bind(this)
        );
    }

    getActions(callback, params ?: any) {
        const itemsArray: Item[] = [];

        return this.api.send(
            this.getApiPath() + '/actions',
            params ? params : [],
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(this.convertFromApi(itemFromApi));
                });

                callback(itemsArray);
            });
    }
}
