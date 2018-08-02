import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './reportprojects-routing.module';
import {LoginService} from '../../auth/login/login.service';

import {HttpClientModule} from '@angular/common/http';
import {ReportProjectsService} from './reportprojects.service';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';
import {ReportProjectsComponent} from './reportprojects.component';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule,
        SharedModule
    ],
    declarations: [
        ReportProjectsComponent
    ],
    providers: [
        LoginService,
        ReportProjectsService
    ]
})

export class ReportProjectsModule {
}
