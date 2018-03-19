import {ApiService} from '../../api/api.service';
import { Component, OnInit } from '@angular/core';
import {Message} from 'primeng/components/common/api';
import {MessageService} from 'primeng/components/common/messageservice';

@Component({
    selector: 'app-integrations',
    templateUrl: './integrations.component.html',
    styleUrls: ['../../app.component.scss'],
})
export class IntegrationsComponent implements OnInit {

    msgs: Message[] = [];
    redmineUrl : string;
    redmineApiKey : string;

    constructor(private api: ApiService) { }

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
        this.redmineUrl = result.redmine_url;
        this.redmineApiKey = result.redmine_api_key;
    }

    redmineUpdateCallback(result) {
        console.log(result);
        this.msgs = [];
        this.msgs.push({severity:'success', summary:'Success Message', detail:'Settings have been updated'});
    }

    synchronizeProjectsCallback(result) {
        this.msgs = [];
        this.msgs.push({
            severity:'success',
            summary:'Projects have been synchronized',
            detail:'New projects: ' + result.added_projects
        });
    }

    synchronizeTasksCallback(result) {
        this.msgs = [];
        this.msgs.push({
            severity:'success',
            summary:'Tasks have been synchronized',
            detail:'New tasks: ' + result.added_tasks
        });
    }
}
