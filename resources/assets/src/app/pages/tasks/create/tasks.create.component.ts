import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Task} from "../../../models/task.model";
import {Router} from "@angular/router";
import {TasksService} from "../tasks.service";
import {ItemsCreateComponent} from "../../items.create.component";


@Component({
    selector: 'app-tasks-create',
    templateUrl: './tasks.create.component.html',
    styleUrls: ['./tasks.create.component.scss']
})
export class TasksCreateComponent extends ItemsCreateComponent implements OnInit {

    public item: Task = new Task();

    constructor(api: ApiService,
                taskService: TasksService,
                router: Router) {
        super(api, taskService, router);
    }

    prepareData() {
        return {
            'project_id': this.item.project_id,
            'task_name': this.item.task_name,
            'active': this.item.active,
            'user_id': this.item.user_id,
            'assigned_by': this.item.assigned_by,
            'url': this.item.url
        }
    }
}
