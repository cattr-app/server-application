import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';

@Component({
    selector: 'app-tasks-list',
    templateUrl: './tasks.list.component.html',
    styleUrls: ['./tasks.list.component.scss']
})
export class TasksListComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('tasks/list', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
