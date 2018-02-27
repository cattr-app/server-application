import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';

@Component({
    selector: 'app-tasks-show',
    templateUrl: './tasks.show.component.html',
    styleUrls: ['./tasks.show.component.scss']
})
export class TasksShowComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('tasks/show', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
