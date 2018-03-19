import {ApiService} from '../../api/api.service';
import { Component, OnInit } from '@angular/core';

@Component({
    selector: 'app-integrations',
    templateUrl: './integrations.component.html',
    styleUrls: ['../../app.component.scss']
})
export class IntegrationsComponent implements OnInit {

    constructor(private api: ApiService) { }
    redmineUrl : string;
    redmineApiKey : string;

    ngOnInit() {
        this.api.getSettings([],  this.getSettingsCallback.bind(this))
    }

    onSubmit() {
        this.api.sendSettings({'redmine_url': this.redmineUrl, 'redmine_key' : this.redmineApiKey},  this.redmineUpdateCallback.bind(this))
    }

    synchronizeProjects() {
        this.api.sendSynchronizeProjects([],  this.synchronizeProjectsCallback.bind(this));
    }

    synchronizeTasks() {
        this.api.sendSynchronizeTasks([],  this.synchronizeTasksCallback.bind(this));
    }

    getSettingsCallback(result) {
        console.log(result);
        this.redmineUrl = result.redmine_url;
        this.redmineApiKey = result.redmine_api_key;
    }

    redmineUpdateCallback(result) {
        console.log(result);
    }

    synchronizeProjectsCallback(result) {
        console.log(result);
    }

    synchronizeTasksCallback(result) {
        console.log(result);
    }
}
