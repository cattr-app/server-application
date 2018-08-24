import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './tasks-routing.module';
import {LoginService} from '../../auth/login/login.service';
import {TasksService} from './tasks.service';
import {UsersService} from '../users/users.service';

import {TasksCreateComponent} from './create/tasks.create.component';
import {TasksEditComponent} from './edit/tasks.edit.component';
import {TasksShowComponent} from './show/tasks.show.component';
import {TasksListComponent} from './list/tasks.list.component';

import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';
import {TranslateModule} from '@ngx-translate/core';
import { AccordionModule } from 'ngx-bootstrap/accordion';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule,
        SharedModule,
        TranslateModule,
        AccordionModule.forRoot(),
    ],
    declarations: [
        TasksCreateComponent,
        TasksEditComponent,
        TasksListComponent,
        TasksShowComponent,
    ],
    providers: [
        LoginService,
        TasksService,
        UsersService
    ]
})

export class TasksModule {
}
