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
        return new Task(
            itemFromApi.id,
            itemFromApi.project_id,
            itemFromApi.task_name,
            itemFromApi.active,
            itemFromApi.user_id,
            itemFromApi.assigned_by,
            itemFromApi.url,
            itemFromApi.created_at,
            itemFromApi.updated_at,
            itemFromApi.deleted_at,
        );
    }
}
