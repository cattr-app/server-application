import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {LoginComponent} from './login/login.component';
import {ForgotComponent} from "./forgot/forgot.component";
import {ResetComponent} from "./reset/reset.component";
import {RegisterComponent} from './register/register.component';

export const AuthRoutes: Routes = [
    {path: 'login', component: LoginComponent},
    {path: 'forgot', component: ForgotComponent},
    {path: 'reset', component: ResetComponent},
    {path: 'register/:key', component: RegisterComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
