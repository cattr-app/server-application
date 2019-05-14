import {Component, EventEmitter, OnInit, Output, ViewChild} from '@angular/core';
import {Router} from "@angular/router";
import { RecaptchaComponent } from 'ng-recaptcha';

import {LoginService} from "./login.service";
import {Login} from "./login.model";
import {ApiService} from "../../api/api.service";

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
})
export class LoginComponent implements OnInit {
    @ViewChild('captcha') captcha: RecaptchaComponent;

    @Output() changeTitle: EventEmitter<String> = new EventEmitter<string>();

    public model: Login = new Login();
    public error?: string = null;

    public siteKey = (window as any).recaptcha_pubkey || '';

    constructor(
        private loginService: LoginService,
        private api: ApiService,
        private router: Router
    ) {}

    ngOnInit() {
        this.changeTitle.emit("Login");
    }

    public onSubmit() {
        this.error = null;
        this.loginService.send(this.model, result => {
            this.api.setToken(result.access_token, result.token_type, result.user);
            this.router.navigateByUrl('/');
        }, error => {
            this.captcha.reset();
            if (error.status === 401) {
                this.error = 'Incorrect password or captcha';
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
