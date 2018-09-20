import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { AuthRoute } from './time-use-report-routing.module';
import { SharedModule } from '../../shared.module';

import { TimeUseReportComponent } from './time-use-report.component';
import { TimeUseReportService } from './time-use-report.service';

@NgModule({
  imports: [
    CommonModule,
    AuthRoute,
    FormsModule,
    SharedModule,
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
