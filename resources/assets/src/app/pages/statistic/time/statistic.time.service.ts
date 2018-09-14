import {Injectable} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TimeDuration} from '../../../models/timeduration.model';
import {ItemsService} from '../../items.service';

@Injectable()
export class TimeDurationService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'time-duration';
    }

    convertFromApi(itemFromApi) {
        return new TimeDuration(itemFromApi);
    }

}
