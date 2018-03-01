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
            this.task.project_id,
            this.task.task_name,
            this.task.active,
            this.task.user_id,
            this.task.assigned_by,
            this.task.url,
            this.createCallback.bind(this)
        );
    }

    createCallback(result) {
        console.log(result);
        this.router.navigateByUrl('/tasks/list');
    }
}
