import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './users-routing.module';
import {LoginService} from "../../auth/login/login.service";

import {UsersCreateComponent} from './create/users.create.component';
import {UsersEditComponent} from './edit/users.edit.component';
import {UsersShowComponent} from './show/users.show.component';
import {UsersListComponent} from './list/users.list.component';

import {HttpClientModule} from '@angular/common/http';
import {UsersService} from "./users.service";

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule
    ],
    declarations: [
        UsersCreateComponent,
        UsersEditComponent,
        UsersListComponent,
        UsersShowComponent,
    ],
    providers: [
        LoginService,
        UsersService
    ]
})

export class UsersModule {
}
