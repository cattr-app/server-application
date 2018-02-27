import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';


@Component({
    selector: 'app-tasks-edit',
    templateUrl: './tasks.edit.component.html',
    styleUrls: ['./tasks.edit.component.scss']
})
export class TasksEditComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('tasks/edit', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
