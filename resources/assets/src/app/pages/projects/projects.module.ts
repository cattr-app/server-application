import {NgModule} from '@angular/core';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './projects-routing.module';
import {UsersService} from '../users/users.service';
import {LoginService} from '../../auth/login/login.service';
import {ProjectsService} from './projects.service';
import { RolesService } from '../roles/roles.service';

import {ProjectsCreateComponent} from './create/projects.create.component';
import {ProjectsEditComponent} from './edit/projects.edit.component';
import {ProjectsShowComponent} from './show/projects.show.component';
import {ProjectsListComponent} from './list/projects.list.component';
import {ProjectsUsersComponent} from './users/projects.users.component';

import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {CommonModule} from '@angular/common';
import {SharedModule} from '../../shared.module';
import {TranslateModule} from '@ngx-translate/core';


@NgModule({
    imports: [
        AuthRoute,
        FormsModule,
        CommonModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule,
        SharedModule,
        TranslateModule,
    ],
    declarations: [
        ProjectsCreateComponent,
        ProjectsEditComponent,
        ProjectsListComponent,
        ProjectsShowComponent,
        ProjectsUsersComponent,
    ],
    providers: [
        LoginService,
        ProjectsService,
        RolesService,
        UsersService
    ]
})

export class ProjectsModule {
}
