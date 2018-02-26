import {Component, EventEmitter, OnInit, Output} from '@angular/core';
import {LoginService} from "./login.service";
import {Login} from "./login.model";
import {ApiService} from "../../api/api.service";
import {Router} from "@angular/router";

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
})
export class LoginComponent implements OnInit {
    @Output() changeTitle: EventEmitter<String> = new EventEmitter<string>();

    public model: Login = new Login();

    constructor(
        private loginService: LoginService,
        private api: ApiService,
        private router: Router
    ) {}

    ngOnInit() {
        this.changeTitle.emit("Login");
    }

    public onSubmit() {
        this.loginService.send(this.model, (result) => {
            this.api.setToken(result.access_token, result.token_type);
            this.router.navigateByUrl('/');
        });
    }
}
