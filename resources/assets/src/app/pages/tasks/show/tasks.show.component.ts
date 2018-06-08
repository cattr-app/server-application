import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from '@angular/router';
import {TasksService} from '../tasks.service';
import {Task} from '../../../models/task.model';
import {ItemsShowComponent} from '../../items.show.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-tasks-show',
    templateUrl: './tasks.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TasksShowComponent extends ItemsShowComponent implements OnInit {

    public item: Task = new Task();

    constructor(api: ApiService,
                taskService: TasksService,
                router: ActivatedRoute,
                allowService: AllowedActionsService
    ) {
        super(api, taskService, router, allowService);
    }
}
