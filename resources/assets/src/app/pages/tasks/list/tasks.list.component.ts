import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TasksService} from "../../tasks/tasks.service";
import {Task} from "../../../models/task.model";
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from "../../items.list.component";
import {AllowedActionsService} from "../../roles/allowed-actions.service";

@Component({
    selector: 'app-tasks-list',
    templateUrl: './tasks.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TasksListComponent extends ItemsListComponent implements OnInit {

    itemsArray: Task[] = [];
    p: number = 1;

    constructor(api: ApiService,
                taskService: TasksService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,) {
        super(api, taskService, modalService, allowedService);
    }
}
