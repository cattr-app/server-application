import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './users-routing.module';
import {LoginService} from '../../auth/login/login.service';
import {RolesService} from '../roles/roles.service';

import {UsersCreateComponent} from './create/users.create.component';
import {UsersInviteComponent} from './invite/users.invite.component';
import {UsersEditComponent} from './edit/users.edit.component';
import {UsersShowComponent} from './show/users.show.component';
import {UsersListComponent} from './list/users.list.component';
import {UsersAttachedUsersComponent} from './users/users.attached-users.component';

import {HttpClientModule} from '@angular/common/http';
import {UsersService} from './users.service';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {NgSelectModule} from '@ng-select/ng-select';
import {TimezonePickerModule} from 'ng2-timezone-selector';
import {TranslateModule} from '@ngx-translate/core';
import {SharedModule} from '../../shared.module';
import { LoadingModule } from 'ngx-loading';
import { TooltipModule } from 'ngx-bootstrap';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule,
        NgSelectModule,
        TimezonePickerModule,
        TranslateModule,
        SharedModule,
        LoadingModule,
        TooltipModule.forRoot(),
    ],
    declarations: [
        UsersCreateComponent,
        UsersInviteComponent,
        UsersEditComponent,
        UsersListComponent,
        UsersShowComponent,
        UsersAttachedUsersComponent,
    ],
    providers: [
        LoginService,
        UsersService,
        RolesService
    ]
})

export class UsersModule {
}
