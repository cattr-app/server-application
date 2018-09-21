import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LoadingModule } from 'ngx-loading';
import { TranslateModule } from '@ngx-translate/core';

import { AuthRoute } from './time-use-report-routing.module';
import { SharedModule } from '../../shared.module'

import { TimeUseReportComponent } from './component/time-use-report.component';

import { TimeUseReportService } from './component/time-use-report.service';

@NgModule({
  imports: [
    CommonModule,
    AuthRoute,
    FormsModule,
    LoadingModule,
    SharedModule,
    TranslateModule,
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
