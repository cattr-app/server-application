import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {HttpClientModule} from '@angular/common/http';
import {TranslateModule} from '@ngx-translate/core';
import { RecaptchaModule } from 'ng-recaptcha';

import {TimezonePickerModule} from 'ng2-timezone-selector';
import {LoadingModule} from 'ngx-loading';

import {AuthRoute} from './auth-routing.module';

import {LoginComponent} from './login/login.component';
import {ResetComponent} from "./reset/reset.component";
import { ConfirmResetComponent } from './comfirm-reset/confirm-reset.component';
import {RegisterComponent} from './register/register.component';

import {LoginService} from "./login/login.service";
import { ResetService } from './reset/reset.service';
import { ConfirmResetService } from './comfirm-reset/confirm-reset.service';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        TranslateModule,
        TimezonePickerModule,
        LoadingModule,
        RecaptchaModule,
    ],
    declarations: [
        LoginComponent,
        ResetComponent,
        ConfirmResetComponent,
        RegisterComponent,
    ],
    providers: [
        LoginService,
        ResetService,
        ConfirmResetService,
    ]
})
export class AuthModule {
}
