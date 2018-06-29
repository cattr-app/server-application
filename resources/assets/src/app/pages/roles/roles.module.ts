import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';

import {RolesRoute} from './roles-routing.module';
import {LoginService} from '../../auth/login/login.service';
import {RolesService} from './roles.service';
import {RulesService} from './rules.service';
import {UsersService} from '../users/users.service';

import {RolesListComponent} from './list/roles.list.component';
import {RolesCreateComponent} from './create/roles.create.component';
import {RolesEditComponent} from './edit/roles.edit.component';
import {RolesShowComponent} from './show/roles.show.component';
import {RolesUsersComponent} from './users/roles.users.component';
import {NgxPaginationModule} from 'ngx-pagination';
import {HttpClientModule} from '@angular/common/http';


@NgModule({
    imports: [
        CommonModule,
        RolesRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule,
        SharedModule
    ],
    declarations: [
        RolesListComponent,
        RolesCreateComponent,
        RolesEditComponent,
        RolesUsersComponent,
        RolesShowComponent,
    ],
    providers: [
        LoginService,
        RolesService,
        RulesService,
        UsersService,
    ]
})

export class RolesModule {
}
