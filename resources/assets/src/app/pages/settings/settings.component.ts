import {ApiService} from '../../api/api.service';
import { Component, OnInit } from '@angular/core';
import {Message} from 'primeng/components/common/api';
import {TranslateService} from '@ngx-translate/core';
import {LocalStorage} from '../../api/storage.model';


@Component({
    selector: 'app-settings',
    templateUrl: './settings.component.html',
    styleUrls: ['../../app.component.scss'],
})


export class SettingsComponent implements OnInit {

    languages = [
        {'code': 'en', 'title': 'English'},
        {'code': 'ru', 'title': 'Русский'},
        {'code': 'dk', 'title': 'Dansk'},
    ]

    language: string = 'en';

    constructor(private api: ApiService,
        protected translate: TranslateService
        ) {
        let lang = translate.currentLang;

        if (lang == null) {
            lang = translate.defaultLang;
        }
        this.language = lang;
    }

    ngOnInit() {
    }

    onSubmit() {

        if (typeof this.language != null) {
            this.translate.use(this.language);
            LocalStorage.getStorage().set('language', this.language);
        }
    }


}
