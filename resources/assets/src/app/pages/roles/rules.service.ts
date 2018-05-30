import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Rule} from '../../models/rule.model';
import {ItemsService} from '../items.service';

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
            this.getApiPath() + '/bulk/edit',
            {'rules': data},
            (result) => {
                callback(result);
            },
            errorCallback ? errorCallback : this.api.errorCallback.bind(this)
        );
    }

}
