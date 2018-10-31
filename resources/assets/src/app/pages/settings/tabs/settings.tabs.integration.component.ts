import { Component } from '@angular/core';

import { Task } from '../../../models/task.model';
import { Project } from '../../../models/project.model';
import { Message } from 'primeng/components/common/api';
import { ApiService } from '../../../api/api.service';


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

    constructor(
        private api: ApiService,
    ) {

        api.getSettings([], result => {
            this.redmineUrl = result.redmine_url;
            this.redmineApiKey = result.redmine_api_key;
            this.redmineStatuses = result.redmine_statuses;
            this.redminePriorities = result.redmine_priorities;
            this.internalPriorities = result.internal_priorities;
        });
    }

    onSubmit() {
        this.api.sendSettings({
            'redmine_url': this.redmineUrl,
            'redmine_key': this.redmineApiKey,
            'redmine_statuses': this.redmineStatuses,
            'redmine_priorities': this.redminePriorities,
        }, () => {
            this.msgs = [{
                severity: 'success',
                summary: 'Success Message',
                detail: 'Settings have been updated',
            }];
        });
    }
}
