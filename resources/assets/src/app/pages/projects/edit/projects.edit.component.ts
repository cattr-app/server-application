import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';


@Component({
    selector: 'app-projects-edit',
    templateUrl: './projects.edit.component.html',
    styleUrls: ['./projects.edit.component.scss']
})
export class ProjectsEditComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('projects/edit', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
