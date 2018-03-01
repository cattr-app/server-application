import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {TasksService} from "../tasks.service";
import {Task} from "../../../models/task.model";

@Component({
    selector: 'app-tasks-show',
    templateUrl: './tasks.show.component.html',
    styleUrls: ['./tasks.show.component.scss']
})
export class TasksShowComponent implements OnInit {
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

    setTask(result) {
        this.task = result;
    }
}
