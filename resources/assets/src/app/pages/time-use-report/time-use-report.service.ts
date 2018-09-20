import { Injectable } from '@angular/core';

import { ApiService } from '../../api/api.service';
import { ItemsService } from '../items.service';

@Injectable()
export class TimeUseReportService extends ItemsService {
    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'time-use-report';
    }

    convertFromApi(itemFromApi) {
        return itemFromApi;
    }
}
