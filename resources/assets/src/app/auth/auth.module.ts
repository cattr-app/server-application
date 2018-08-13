import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './auth-routing.module';
import {LoginComponent} from './login/login.component';
import {LoginService} from "./login/login.service";

import {HttpClientModule} from '@angular/common/http';
import {ForgotComponent} from "./forgot/forgot.component";
import {ResetComponent} from "./reset/reset.component";
import {ApiService} from "../api/api.service";
import {Router} from "@angular/router";
import {TranslateModule} from '@ngx-translate/core';


@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        TranslateModule,
    ],
    declarations: [
        LoginComponent,
        ForgotComponent,
        ResetComponent,
    ],
    providers: [
        LoginService
    ]
})
export class AuthModule {
}
