import { ApiService } from '../../api/api.service';
import { Component, OnInit } from '@angular/core';
import { Message } from 'primeng/components/common/api';
import { TranslateService } from '@ngx-translate/core';
import { LocalStorage } from '../../api/storage.model';

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
    selector: 'app-settings',
    templateUrl: './settings.component.html',
    styleUrls: ['./settings.component.scss', '../../app.component.scss'],
})
export class SettingsComponent {
    msgs: Message[] = [];

    languages = [
        { 'code': 'en', 'title': 'English' },
        { 'code': 'ru', 'title': 'Русский' },
        { 'code': 'dk', 'title': 'Dansk' },
    ];
    language: string = 'en';

    redmineUrl: string;
    redmineApiKey: string;
    redmineStatuses: RedmineStatus[] = [];
    redminePriorities: RedminePriority[] = [];
    internalPriorities: Priority[] = [];

    constructor(
        private api: ApiService,
        protected translate: TranslateService,
    ) {
        this.language = translate.currentLang
            ? translate.currentLang
            : translate.defaultLang;

        this.api.getSettings([], result => {
            this.redmineUrl = result.redmine_url;
            this.redmineApiKey = result.redmine_api_key;
            this.redmineStatuses = result.redmine_statuses;
            this.redminePriorities = result.redmine_priorities;
            this.internalPriorities = result.internal_priorities;
        });
    }

    onSubmit() {
        if (typeof this.language != null) {
            this.translate.use(this.language);
            LocalStorage.getStorage().set('language', this.language);
        }

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
