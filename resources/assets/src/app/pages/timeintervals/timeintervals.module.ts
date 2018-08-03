import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './timeintervals-routing.module';

import {TimeIntervalsCreateComponent} from './create/timeintervals.create.component';
import {TimeIntervalsEditComponent} from './edit/timeintervals.edit.component';
import {TimeIntervalsListComponent} from './list/timeintervals.list.component';
import {TimeIntervalsShowComponent} from './show/timeintervals.show.component';

import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';
import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';

import {LoginService} from '../../auth/login/login.service';
import {ScreenshotsService} from '../screenshots/screenshots.service';
import {TimeIntervalsService} from './timeintervals.service';
import {TasksService} from "../tasks/tasks.service";
import {UsersService} from "../users/users.service";
import {TranslateModule} from '@ngx-translate/core';

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
    ],
    declarations: [
        TimeIntervalsCreateComponent,
        TimeIntervalsEditComponent,
        TimeIntervalsListComponent,
        TimeIntervalsShowComponent,
    ],
    providers: [
        LoginService,
        TimeIntervalsService,
        ScreenshotsService,
        TasksService,
        UsersService
    ]
})

export class TimeIntervalsModule {
}
