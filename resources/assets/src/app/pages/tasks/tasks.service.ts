import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Task} from "../../models/task.model";
import {ProjectsService} from "../projects/projects.service";

@Injectable()
export class TasksService {

    constructor(private api: ApiService) {
    }

    createTask(projectId, taskName, active, userId, assignedBy, url, callback) {
        this.api.send(
            'tasks/create',
            {
                'project_id': projectId,
                'task_name': taskName,
                'active': active,
                'user_id': userId,
                'assigned_by': assignedBy,
                'url': url
            },
            (result) => {
                callback(result);
            }
        );
    }

    editTask(taskId, projectId, taskName, active, userId, assignedBy, url, callback) {
        this.api.send(
            'tasks/edit',
            {
                'task_id': taskId,
                'project_id': projectId,
                'task_name': taskName,
                'active': active,
                'user_id': userId,
                'assigned_by': assignedBy,
                'url': url
            },
            (result) => {
                callback(result);
            }
        );
    }

    getTask(taskId, callback) {
        let task: Task;

        return this.api.send(
            'tasks/show',
            {'task_id': taskId},
            (taskFromApi) => {
                task = TasksService.convertFromApi(taskFromApi)
                callback(task);
            });
    }

    getTasks(callback) {
        let tasksArray: Task[] = [];

        return this.api.send(
            'tasks/list',
            [],
            (result) => {
                result.data.forEach(function (taskFromApi) {
                    tasksArray.push(TasksService.convertFromApi(taskFromApi));
                });

                callback(tasksArray);
            });
    }

    removeTask(taskId, callback) {
        this.api.send(
            'tasks/remove',
            {
                'task_id': taskId,
            },
            (result) => {
                callback(result);
            }
        );
    }

    static convertFromApi(taskFromApi) {
        return new Task(
            taskFromApi.id,
            taskFromApi.project_id,
            taskFromApi.task_name,
            taskFromApi.active,
            taskFromApi.user_id,
            taskFromApi.assigned_by,
            taskFromApi.url,
            taskFromApi.created_at,
            taskFromApi.updated_at,
            taskFromApi.deleted_at,
        )
    }
}
