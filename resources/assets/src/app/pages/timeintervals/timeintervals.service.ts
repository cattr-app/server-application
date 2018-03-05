import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {TimeInterval} from "../../models/timeinterval.model";
import {ItemsService} from "../items.service";


@Injectable()
export class TimeIntervalsService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'timeintervals';
    }

    convertFromApi(itemFromApi) {
        return new TimeInterval(itemFromApi);
    }
}
