import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';


@Component({
    selector: 'app-projects-list',
    templateUrl: './projects.list.component.html',
    styleUrls: ['./projects.list.component.scss']
})
export class ProjectsListComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('projects/list', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
