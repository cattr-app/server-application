import { Component, OnInit, Output, EventEmitter, OnDestroy } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Subscription } from 'rxjs';

import { ConfirmResetService } from './confirm-reset.service';
import { ApiService } from '../../api/api.service';

import { ConfirmReset } from './confirm-reset.model';

@Component({
    selector: 'app-confirm-reset',
    templateUrl: './confirm-reset.component.html',
})
export class ConfirmResetComponent implements OnInit, OnDestroy {
    @Output() changeTitle: EventEmitter<String> = new EventEmitter<string>();

    public model: ConfirmReset = new ConfirmReset();
    public error?: string = null;
    public siteKey = (window as any).recaptcha_pubkey || '';

    private routeSub: Subscription;

    constructor(
        private resetService: ConfirmResetService,
        private api: ApiService,
        private router: Router,
        private route: ActivatedRoute
    ) { }

    ngOnInit() {
        this.changeTitle.emit("Reset password");

        this.routeSub = this.route.params.subscribe(params => {
            this.model.token = params['token'];
        });
    }

    ngOnDestroy() {
        this.routeSub.unsubscribe();
    }

    public onSubmit() {
        this.error = null;
        this.resetService.send(this.model, result => {
            this.api.setToken(result.access_token, result.token_type, result.user);
            this.router.navigateByUrl('/');
        }, error => {
            if (error.status === 401) {
                this.error = 'Incorrect email or captcha';
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
