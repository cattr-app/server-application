import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../api/api.service';

@Component({
    selector: 'app-reportprojects',
    templateUrl: './reportprojects.component.html',
    styleUrls: []
})
export class ReportProjectsComponent implements OnInit {
    constructor(private api: ApiService) {
    }

    ngOnInit() {

    }
}
