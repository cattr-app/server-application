import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';

@Component({
    selector: 'app-projects-show',
    templateUrl: './projects.show.component.html',
    styleUrls: ['./projects.show.component.scss']
})
export class ProjectsShowComponent implements OnInit {
    constructor(private api: ApiService) { }

    //items: Project[] = [];

    ngOnInit() {

    }

    onTest() {
        this.api.send('projects/show', [], this.result);
    }

    result(res) {
        console.log(res);
    }
}
