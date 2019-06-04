import {Component, Output, EventEmitter, OnDestroy} from '@angular/core';
import {NgModel} from '@angular/forms';

import {Message} from 'primeng/components/common/api';
import {ApiService} from '../../../api/api.service';

@Component({
    selector: 'app-settings-integration-gitlab',
    templateUrl: './settings.tabs.integration-gitlab.component.html'
})
export class IntegrationGitlabComponent implements OnDestroy {

    gitlabUrl: string;
    gitlabApiKey: string;
    @Output() message: EventEmitter<Message> = new EventEmitter<Message>();

    constructor(private api: ApiService) {
        this.loadSettings();
    }

    onSubmit() {
        this.api.sendGitlabSettings({
            'url': this.gitlabUrl,
            'apikey': this.gitlabApiKey
        }, () => {
            this.loadSettings();
            this.message.emit({
                severity: 'success',
                summary: 'Success Message',
                detail: 'Settings have been setted',
            });
        }, (result) => {
            this.message.emit({
                severity: 'error',
                summary: result.error.error,
                detail: result.error.reason,
            });
        });
    }

    protected loadSettings() {
        try {
            this.api.getGitlabSettings([], result => {
                this.gitlabUrl = result.url;
                this.gitlabApiKey = result.apikey;
            });
        } catch (err) {
            console.error(err);
        }
    }


    cleanupParams(): string[] {
        return [
            'url',
            'apikey'
        ];
    }

    ngOnDestroy() {
        for (const param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
