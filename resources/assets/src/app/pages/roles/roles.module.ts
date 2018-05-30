import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {GrowlModule} from 'primeng/growl';

import {RolesRoute} from './roles-routing.module';
import {LoginService} from '../../auth/login/login.service';
import {RolesService} from './roles.service';
import {RulesService} from './rules.service';
import {ActionsService} from './actions.service';
import {UsersService} from '../users/users.service';

import {RolesListComponent} from './list/roles.list.component';
import {RolesCreateComponent} from './create/roles.create.component';
import {RolesEditComponent} from './edit/roles.edit.component';
import {RolesShowComponent} from './show/roles.show.component';
import {RolesUsersComponent} from './users/roles.users.component';
import {NgxPaginationModule} from 'ngx-pagination';
import {HttpClientModule} from '@angular/common/http';
import {DualListComponent} from 'angular-dual-listbox';


@NgModule({
    imports: [
        CommonModule,
        RolesRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule
    ],
    declarations: [
        RolesListComponent,
        RolesCreateComponent,
        RolesEditComponent,
        RolesUsersComponent,
        RolesShowComponent,
        DualListComponent,
    ],
    providers: [
        LoginService,
        RolesService,
        ActionsService,
        RulesService,
        UsersService,
    ]
})

export class RolesModule {
}
