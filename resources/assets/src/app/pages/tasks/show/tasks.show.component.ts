import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {TasksService} from "../tasks.service";
import {Task} from "../../../models/task.model";
import {ItemsShowComponent} from "../../items.show.component";

@Component({
    selector: 'app-tasks-show',
    templateUrl: './tasks.show.component.html',
    styleUrls: ['./tasks.show.component.scss']
})
export class TasksShowComponent extends ItemsShowComponent implements OnInit {

    public item: Task = new Task();

    constructor(api: ApiService,
                taskService: TasksService,
                router: ActivatedRoute) {
        super(api, taskService, router);
    }
}
