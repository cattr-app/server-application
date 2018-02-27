import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';


@Component({
    selector: 'app-projects-create',
    templateUrl: './projects.create.component.html',
    styleUrls: ['./projects.create.component.scss']
})
export class ProjectsCreateComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('projects/create', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
