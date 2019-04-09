import { Component, Output, EventEmitter } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { Message } from 'primeng/components/common/api';
import { LocalStorage } from '../../../api/storage.model';


@Component({
    selector: 'settings-general',
    templateUrl: './settings.tabs.general.component.html'
})
export class GeneralComponent {
    language: string = 'en';
    languages = [
        { 'code': 'en', 'title': 'English' },
        { 'code': 'ru', 'title': 'Русский' },
        { 'code': 'dk', 'title': 'Dansk' },
    ];

    @Output() message: EventEmitter<Message> = new EventEmitter<Message>();

    constructor(
        protected translate: TranslateService,
    ) {
        this.language = translate.currentLang
            ? translate.currentLang
            : translate.defaultLang;
    }

    onSubmit() {
        try {
            if (typeof this.language != null) {
                this.translate.use(this.language);
                LocalStorage.getStorage().set('language', this.language);
            }

            this.message.emit({
                severity: 'success',
                summary: 'Success Message',
                detail: 'Settings have been updated',
            });
        } catch (e) {
            this.message.emit({
                severity: 'error',
                summary: 'Error Message',
                detail: e.message,
            });
        }
    }

}
