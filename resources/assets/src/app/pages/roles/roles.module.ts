import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {RolesRoute} from './roles-routing.module';
import {LoginService} from "../../auth/login/login.service";
import {RolesService} from "./roles.service";
import {RulesService} from "./rules.service";
import {ActionsService} from "./actions.service";

import {RolesListComponent} from './list/roles.list.component';
import {RolesCreateComponent} from './create/roles.create.component';
import {RolesEditComponent} from './edit/roles.edit.component';
import {NgxPaginationModule} from 'ngx-pagination';

import {HttpClientModule} from '@angular/common/http';

@NgModule({
    imports: [
        CommonModule,
        RolesRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule
    ],
    declarations: [
        RolesListComponent,
        RolesCreateComponent,
        RolesEditComponent,
    ],
    providers: [
        LoginService,
        RolesService,
        ActionsService,
        RulesService,
    ]
})

export class RolesModule {
}
