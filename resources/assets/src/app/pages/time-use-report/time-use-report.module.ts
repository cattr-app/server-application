import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LoadingModule } from 'ngx-loading';

import { AuthRoute } from './time-use-report-routing.module';
import { SharedModule } from '../../shared.module';
import { UsersModule } from '../users/users.module';

import { TimeUseReportComponent } from './time-use-report.component';

import { TimeUseReportService } from './time-use-report.service';

@NgModule({
  imports: [
    CommonModule,
    AuthRoute,
    FormsModule,
    LoadingModule,
    SharedModule,
    UsersModule,
  ],
  exports: [
    TimeUseReportComponent,
  ],
  declarations: [
    TimeUseReportComponent,
  ],
  providers: [
    TimeUseReportService,
  ]
})
export class TimeUseReportModule {
}
