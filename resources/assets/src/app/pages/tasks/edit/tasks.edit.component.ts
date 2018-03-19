import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Task} from "../../../models/task.model";
import {Router, ActivatedRoute} from "@angular/router";
import {TasksService} from "../tasks.service";
import {ItemsEditComponent} from "../../items.edit.component";


@Component({
    selector: 'app-tasks-edit',
    templateUrl: './tasks.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TasksEditComponent extends ItemsEditComponent implements OnInit {

    public item: Task = new Task();

    constructor(api: ApiService,
                taskService: TasksService,
                activatedRoute: ActivatedRoute,
                router: Router) {
        super(api, taskService, activatedRoute, router)
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
