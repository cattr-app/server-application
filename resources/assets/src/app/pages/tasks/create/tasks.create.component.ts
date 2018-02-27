import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';


@Component({
    selector: 'app-tasks-create',
    templateUrl: './tasks.create.component.html',
    styleUrls: ['./tasks.create.component.scss']
})
export class TasksCreateComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('tasks/create', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
