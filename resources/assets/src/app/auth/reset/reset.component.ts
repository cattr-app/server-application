import { Component, OnInit, Output, EventEmitter } from '@angular/core';
import { Router } from '@angular/router';

import { ResetService } from './reset.service';
import { Reset } from './reset.model';

@Component({
    selector: 'app-reset',
    templateUrl: './reset.component.html',
})
export class ResetComponent implements OnInit {
    @Output() changeTitle: EventEmitter<String> = new EventEmitter<string>();

    public model: Reset = new Reset();
    public error?: string = null;

    public siteKey = (window as any).recaptcha_pubkey || '';

    constructor(
        private resetService: ResetService,
        private router: Router
    ) { }

    ngOnInit() {
        this.changeTitle.emit("Reset password");
    }

    public onSubmit() {
        this.error = null;
        this.resetService.send(this.model, () => {
            this.router.navigateByUrl('/');
        }, error => {
            if (error.status === 401) {
                this.error = 'Incorrect captcha';
            } else if (error.status === 0) {
                this.error = 'Connection problem';
            } else {
                this.error = error.statusText;
            }
        });
    }

    public captchaResolved(token: string) {
        this.model.recaptcha = token;
    }
}
