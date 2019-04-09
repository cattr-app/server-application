import { Component, Output, EventEmitter } from '@angular/core';
import { NgModel } from '@angular/forms';

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

    redmine_sync: boolean;
    redmine_active_status: number;
    redmine_deactive_status: number;
    redmineActivateStatuses: boolean[] = [];
    redmineDeactivateStatuses: boolean[] = [];
    redmine_online_timeout: number;

    @Output() message: EventEmitter<Message> = new EventEmitter<Message>();

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

                this.redmineActivateStatuses = this.toInputCheckboxArray(result.redmine_activate_statuses);
                this.redmineDeactivateStatuses = this.toInputCheckboxArray(result.redmine_deactivate_statuses);


                console.log(result);
            });
        } catch(err) {
            console.log(err)
        }
    }

    onSubmit() {
        this.api.sendSettings({
            'redmine_url': this.redmineUrl,
            'redmine_key': this.redmineApiKey,
            'redmine_statuses': this.redmineStatuses,
            'redmine_priorities': this.redminePriorities,
            'redmine_active_status': this.redmine_active_status,
            'redmine_deactive_status': this.redmine_deactive_status,
            'redmine_activate_statuses': this.fromInputCheckboxArray(this.redmineActivateStatuses),
            'redmine_deactivate_statuses': this.fromInputCheckboxArray(this.redmineDeactivateStatuses),
            'redmine_online_timeout': this.redmine_online_timeout,
            'redmine_sync': this.redmine_sync,
        }, () => {
            this.message.emit({
                severity: 'success',
                summary: 'Success Message',
                detail: 'Settings have been updated',
            });
        }, (result) => {
            this.message.emit({
                severity: 'error',
                summary: result.error.error,
                detail: result.error.reason,
            });
        });
    }

    protected toInputCheckboxArray(input) : boolean[] {
        let ret: boolean[] = [];

        if (input) {
            for (let key of input) {
                ret[key] = true;
            }
        }

        return ret;
    }

    protected fromInputCheckboxArray(input): number[] {
        let ret: number[] = [];

        for (let id in input) {
            if (input[id]) {
                ret.push(Number(id));
            }
        }

        return ret;
    }

    isDisplayError(model: NgModel) : boolean {
        return model.invalid && (model.dirty || model.touched);
    }

    isDisplaySuccess(model: NgModel) : boolean {
        return model.valid && (model.dirty || model.touched);
    }
}
