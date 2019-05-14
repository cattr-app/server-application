import { Component, OnInit, Output, EventEmitter, ViewChild } from '@angular/core';
import { RecaptchaComponent } from 'ng-recaptcha';
import { TranslateService } from '@ngx-translate/core';

import { ResetService } from './reset.service';
import { Reset } from './reset.model';

@Component({
    selector: 'app-reset',
    templateUrl: './reset.component.html',
})
export class ResetComponent implements OnInit {
    @ViewChild('captcha') captcha: RecaptchaComponent;

    @Output() changeTitle: EventEmitter<String> = new EventEmitter<string>();

    public model: Reset = new Reset();
    public message?: string = null;
    public error?: string = null;

    public siteKey = (window as any).recaptcha_pubkey || '';

    constructor(
        private resetService: ResetService,
        private translate: TranslateService,
    ) { }

    ngOnInit() {
        this.changeTitle.emit("Reset password");
    }

    public onSubmit() {
        this.message = null;
        this.error = null;

        this.resetService.send(this.model, () => {
            this.captcha.reset();
            this.translate.get('reset.sent').subscribe(res => this.message = res);
        }, error => {
            this.captcha.reset();
            if (error.status === 401) {
                this.translate.get('reset.invalid-captcha').subscribe(res => this.error = res);
            } else if (error.status === 404) {
                this.translate.get('reset.user-not-found').subscribe(res => this.error = res);
            } else if (error.status === 0) {
                this.translate.get('reset.connection-problem').subscribe(res => this.error = res);
            } else {
                this.error = error.statusText;
            }
        });
    }

    public captchaResolved(token: string) {
        this.model.recaptcha = token;
    }
}
