import { Component } from '@angular/core';

import { Task } from '../../../models/task.model';
import { Project } from '../../../models/project.model';
import { Message } from 'primeng/components/common/api';
import { ApiService } from '../../../api/api.service';
import { NgModel } from '@angular/forms';


interface RedmineStatus {
    id: number;
    name: string;
    is_active: boolean;
}

interface RedminePriority {
    id: number;
    name: string;
    priority_id: number;
}

interface Priority {
    id: number;
    name: string;
}


@Component({
    selector: 'settings-integration',
    templateUrl: './settings.tabs.integration.component.html'
})
export class IntegrationComponent {

    redmineUrl: string;
    redmineApiKey: string;
    redmineStatuses: RedmineStatus[] = [];
    redminePriorities: RedminePriority[] = [];
    internalPriorities: Priority[] = [];
    msgs: Message[] = [];

    redmine_sync: boolean;
    redmine_active_status: number;
    redmine_deactive_status: number;
    redmineIgnoreStatuses: boolean[] = [];
    redmine_online_timeout: number;




    constructor(
        private api: ApiService,
    ) {
        try {
            api.getSettings([], result => {
                this.redmineUrl = result.redmine_url;
                this.redmineApiKey = result.redmine_api_key;
                this.redmineStatuses = result.redmine_statuses;
                this.redminePriorities = result.redmine_priorities;
                this.internalPriorities = result.internal_priorities;
                this.redmine_active_status = result.redmine_active_status;
                this.redmine_deactive_status = result.redmine_deactive_status;
                this.redmine_online_timeout = result.redmine_online_timeout;
                this.redmine_sync = result.redmine_sync;

                this.redmineIgnoreStatuses = [];

                for (let status of result.redmine_ignore_statuses) {
                    this.redmineIgnoreStatuses[status] = true;
                }


                console.log(result);
            });
        } catch(err) {
            console.log(err)
        }
    }

    onSubmit() {
        let statuses: number[] = [];

        for (let status_id in this.redmineIgnoreStatuses) {
            if (this.redmineIgnoreStatuses[status_id]) {
                statuses.push(Number(status_id));
            }
        }

        this.api.sendSettings({
            'redmine_url': this.redmineUrl,
            'redmine_key': this.redmineApiKey,
            'redmine_statuses': this.redmineStatuses,
            'redmine_priorities': this.redminePriorities,
            'redmine_active_status': this.redmine_active_status,
            'redmine_deactive_status': this.redmine_deactive_status,
            'redmine_ignore_statuses': statuses,
            'redmine_online_timeout': this.redmine_online_timeout,
            'redmine_sync': this.redmine_sync,
        }, () => {
            this.msgs = [{
                severity: 'success',
                summary: 'Success Message',
                detail: 'Settings have been updated',
            }];
        });
    }

    isDisplayError(model: NgModel) : boolean {
        return model.invalid && (model.dirty || model.touched);
    }

    isDisplaySuccess(model: NgModel) : boolean {
        return model.valid && (model.dirty || model.touched);
    }
}
