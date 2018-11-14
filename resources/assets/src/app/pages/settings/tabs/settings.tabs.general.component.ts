import { Component } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
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


    constructor(
        protected translate: TranslateService,
    ) {
        this.language = translate.currentLang
            ? translate.currentLang
            : translate.defaultLang;
    }

    onSubmit() {
        if (typeof this.language != null) {
            this.translate.use(this.language);
            LocalStorage.getStorage().set('language', this.language);
        }
    }

}
