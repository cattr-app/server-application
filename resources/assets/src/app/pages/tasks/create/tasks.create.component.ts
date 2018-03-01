import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Task} from "../../../models/task.model";
import {Router} from "@angular/router";
import {TasksService} from "../tasks.service";


@Component({
    selector: 'app-tasks-create',
    templateUrl: './tasks.create.component.html',
    styleUrls: ['./tasks.create.component.scss']
})
export class TasksCreateComponent implements OnInit {

    public task: Task = new Task();

    constructor(private api: ApiService,
                private taskService: TasksService,
                private router: Router) {
    }

    ngOnInit() {

    }

    public onSubmit() {
        this.taskService.createTask(
            this.prepareData(),
            this.createCallback.bind(this)
        );
    }

    prepareData() {
        return {
            'project_id': this.task.project_id,
            'task_name': this.task.task_name,
            'active': this.task.active,
            'user_id': this.task.user_id,
            'assigned_by': this.task.assigned_by,
            'url': this.task.url
        }
    }

    createCallback(result) {
        console.log(result);
        this.router.navigateByUrl('/tasks/list');
    }
}
