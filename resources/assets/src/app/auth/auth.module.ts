import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {HttpClientModule} from '@angular/common/http';
import {TranslateModule} from '@ngx-translate/core';

import {TimezonePickerModule} from 'ng2-timezone-selector';
import {LoadingModule} from 'ngx-loading';

import {AuthRoute} from './auth-routing.module';

import {LoginComponent} from './login/login.component';
import {ForgotComponent} from "./forgot/forgot.component";
import {ResetComponent} from "./reset/reset.component";
import {RegisterComponent} from './register/register.component';

import {LoginService} from "./login/login.service";

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        TranslateModule,
        TimezonePickerModule,
        LoadingModule,
    ],
    declarations: [
        LoginComponent,
        ForgotComponent,
        ResetComponent,
        RegisterComponent,
    ],
    providers: [
        LoginService,
    ]
})
export class AuthModule {
}
