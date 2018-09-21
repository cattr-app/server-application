import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './projectsreport-routing.module';
import {LoginService} from '../../auth/login/login.service';

import {ProjectsreportComponent} from './time/projectsreport.component';

import {UsersService} from '../users/users.service';
import {TimeIntervalsService} from '../timeintervals/timeintervals.service';


import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';
import {TranslateModule} from '@ngx-translate/core';

import {ScheduleModule} from 'primeng/schedule';
import {DpDatePickerModule} from 'ng2-date-picker';
import {LoadingModule} from 'ngx-loading';
import {TimezonePickerModule} from 'ng2-timezone-selector';
import {NgSelectModule} from '@ng-select/ng-select';
import { TasksModule } from '../tasks/tasks.module';
import { ProjectsModule } from '../projects/projects.module';
import { PopoverModule } from 'ngx-bootstrap';
import { ScreenshotsModule } from '../screenshots/screenshots.module';
import {ProjectReportService} from './time/projectsreport.service';

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
        ScheduleModule,
        DpDatePickerModule,
        LoadingModule,
        TimezonePickerModule,
        NgSelectModule,
        TasksModule,
        ProjectsModule,
        PopoverModule.forRoot(),
        ScreenshotsModule,
    ],
    exports: [
        ProjectsreportComponent
    ],
    declarations: [
        /**
         * @todo add here the other components, except for static for time usage
         */
        ProjectsreportComponent,
    ],
    providers: [
        LoginService,
        UsersService,
        TimeIntervalsService,
        ProjectReportService,
    ]
})

export class ProjectsreportModule {
}
