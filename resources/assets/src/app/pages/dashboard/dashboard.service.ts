import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Item} from "../../models/item.model";
import {Task} from "../../models/task.model";

@Injectable()
export class DashboardService {


    constructor(protected api: ApiService) {
    }


    getTasks(callback) {
        let itemsArray: Item[] = [];

        return this.api.send(
            'tasks/dashboard',
            [],
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(new Task(
                        itemFromApi.id,
                        itemFromApi.project_id,
                        itemFromApi.task_name,
                        itemFromApi.description,
                        itemFromApi.active,
                        itemFromApi.user_id,
                        itemFromApi.assigned_by,
                        itemFromApi.url,
                        itemFromApi.created_at,
                        itemFromApi.updated_at,
                        itemFromApi.deleted_at,
                        itemFromApi.total_time,
                    ));
                });

                callback(itemsArray);
            });

    }
}

