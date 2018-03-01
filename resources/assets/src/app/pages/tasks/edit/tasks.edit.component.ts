import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Task} from "../../../models/task.model";
import {ActivatedRoute} from "@angular/router";
import {TasksService} from "../tasks.service";


@Component({
    selector: 'app-tasks-edit',
    templateUrl: './tasks.edit.component.html',
    styleUrls: ['./tasks.edit.component.scss']
})
export class TasksEditComponent implements OnInit {
    id: number;
    private sub: any;
    public task: Task = new Task();

    constructor(private api: ApiService,
                private taskService: TasksService,
                private router: ActivatedRoute) {
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.taskService.getTask(this.id, this.setTask.bind(this));
    }

    public onSubmit() {
        this.taskService.editTask(
            this.id,
            this.task.project_id,
            this.task.task_name,
            this.task.active,
            this.task.user_id,
            this.task.assigned_by,
            this.task.url,
            this.editCallback.bind(this)
        );
    }

    setTask(result) {
        this.task = result;
    }

    editCallback(result) {
        console.log("Updated");
    }
}
