import { EventEmitter, Injectable, Output } from '@angular/core';
import { ApiService } from "../../api/api.service";
import { TimeInterval } from "../../models/timeinterval.model";
import { ItemsService } from "../items.service";


@Injectable()
export class TimeIntervalsService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'time-intervals';
    }

    convertFromApi(itemFromApi) {
        return new TimeInterval(itemFromApi);
    }

    removeItems(ids, callback) {
        this.api.send(
            this.getApiPath() + '/bulk-remove',
            {
                'intervals': ids.map(id => {
                    return { id };
                }),
            },
            (result) => {
                callback(result);
            }
        );
    }
}
