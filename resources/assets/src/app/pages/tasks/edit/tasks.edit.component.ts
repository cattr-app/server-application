import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Task} from "../../../models/task.model";
import {ActivatedRoute} from "@angular/router";
import {TasksService} from "../tasks.service";
import {ItemsEditComponent} from "../../items.edit.component";

@Component({
    selector: 'app-tasks-edit',
    templateUrl: './tasks.edit.component.html',
    styleUrls: ['./tasks.edit.component.scss']
})
export class TasksEditComponent extends ItemsEditComponent implements OnInit {

    public item: Task;

    constructor(api: ApiService,
                taskService: TasksService,
                router: ActivatedRoute) {
        super(api, taskService, router)
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
