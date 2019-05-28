import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {LoginComponent} from './login/login.component';
import {ResetComponent} from "./reset/reset.component";
import { ConfirmResetComponent } from './comfirm-reset/confirm-reset.component';
import {RegisterComponent} from './register/register.component';

export const AuthRoutes: Routes = [
    {path: 'login', component: LoginComponent},
    {path: 'reset', component: ResetComponent},
    {path: 'confirm-reset', component: ConfirmResetComponent},
    {path: 'register/:key', component: RegisterComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
