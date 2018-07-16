import {Injectable} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Item} from '../../models/item.model';
import {Task} from '../../models/task.model';
import {Screenshot} from '../../models/screenshot.model';

@Injectable()
export class DashboardService {

    constructor(protected api: ApiService) {
    }

    getTasks(callback, params ?: any) {
        const itemsArray: Item[] = [];

        return this.api.send(
            'tasks/dashboard',
            params ? params : [],
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(new Task(itemFromApi));
                });

                callback(itemsArray);
            });

    }

    getScreenshots(limit, offset, callback, userId) {
        const itemsArray: Item[] = [];

        return this.api.send(
            'screenshots/dashboard',
            {
                'user_id': userId,
                'limit': limit,
                'offset': offset,
                'with': 'timeInterval,timeInterval.task,timeInterval.task.project'
            },
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(new Screenshot(itemFromApi));
                });

                callback(itemsArray);
            });
    }
}

