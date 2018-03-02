import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TasksService} from "../../tasks/tasks.service";
import {Task} from "../../../models/task.model";
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from "../../items.list.component";

@Component({
    selector: 'app-tasks-list',
    templateUrl: './tasks.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TasksListComponent extends ItemsListComponent implements OnInit {

    itemsArray: Task[] = [];

    constructor(api: ApiService,
                taskService: TasksService,
                modalService: BsModalService,) {
        super(api, taskService, modalService);
    }
}
