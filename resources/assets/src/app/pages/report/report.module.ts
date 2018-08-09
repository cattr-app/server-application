import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './report-routing.module';
import {LoginService} from '../../auth/login/login.service';

import {ReportProjectsComponent} from './projects/report.projects.component';


import {UsersService} from '../users/users.service';
import {TimeIntervalsService} from '../timeintervals/timeintervals.service';


import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';
import {ScheduleModule} from 'primeng/schedule';
import {DpDatePickerModule} from 'ng2-date-picker';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule,
        SharedModule,
        ScheduleModule,
        DpDatePickerModule,
    ],
    exports: [
        ReportProjectsComponent
    ],
    declarations: [
        /**
         * @todo add here the other components, except for static for time usage
         */
        ReportProjectsComponent,
    ],
    providers: [
        LoginService,
        UsersService,
        TimeIntervalsService,
    ]
})

export class ReportModule {
}
