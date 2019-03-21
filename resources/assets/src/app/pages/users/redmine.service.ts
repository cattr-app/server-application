import {Injectable} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {RedmineStatus} from '../../models/redmine-status.model';

type statusesCallback = (redmineStatuses: RedmineStatus[]) => void;

@Injectable()
export class RedmineService {

    redmineStatuses: RedmineStatus[] = [];


    constructor(protected api: ApiService) {
    }

    getApiPath() {
        return 'redmine';
    }


    getStatuses(callback: statusesCallback) {
        if (this.redmineStatuses.length) {
            return callback(this.redmineStatuses);
        }

        return this.requireStatuses(callback);
    }


    requireStatuses(callback: statusesCallback) {
        return this.api.send(
            this.getApiPath() + '/statuses',
            {},
            (result) => {
                let itemsArray: RedmineStatus[] = [];

                result.forEach((itemFromApi) => {
                    itemsArray.push(this.convertFromApi(itemFromApi));
                });

                this.onStatusesReceive(itemsArray);
                callback(itemsArray);
            });
    }



    onStatusesReceive(redmineStatuses: RedmineStatus[]) {
        this.redmineStatuses = redmineStatuses;
    }


    convertFromApi(itemFromApi) {
        return new RedmineStatus(itemFromApi);
    }

}
