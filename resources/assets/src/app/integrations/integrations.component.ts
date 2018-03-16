import {ApiService} from '../api/api.service';
import { Component, OnInit } from '@angular/core';

@Component({
    selector: 'app-integrations',
    templateUrl: './integrations.component.html',
    styleUrls: ['../app.component.scss']
})
export class IntegrationsComponent implements OnInit {

    constructor(private api: ApiService) { }
    redmineUrl : string;
    redmineApiKey : string;

    ngOnInit() {

    }

    onSubmit() {
        this.api.sendSettings({'redmine_url': this.redmineUrl, 'redmine_key' : this.redmineApiKey},  this.redmineUpdateCallback.bind(this))
    }

    redmineUpdateCallback(result) {
        console.log(result);
    }
}
