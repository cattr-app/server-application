import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Task} from "../../models/task.model";
import {ItemsService} from "../items.service";


@Injectable()
export class TasksService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'tasks';
    }

    convertFromApi(itemFromApi) {
        return new Task(itemFromApi);
    }
}
