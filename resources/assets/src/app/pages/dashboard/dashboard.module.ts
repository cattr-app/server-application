import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { AuthRoute } from './dashboard-routing.module';
import { LoginService } from "../../auth/login/login.service";
import { DashboardService } from "./dashboard.service";

import { HttpClientModule } from '@angular/common/http';
import { NgxPaginationModule } from 'ngx-pagination';

import { DashboardComponent } from './dashboard.component';
import { TaskListComponent } from './tasklist/tasks.list.component';
import { ScreenshotListComponent } from './screenshotlist/screenshot.list.component';

import {TranslateModule} from '@ngx-translate/core';
import { StatisticModule } from '../statistic/statistic.module';
import { TabsModule } from 'ngx-bootstrap';
import { LoadingModule } from 'ngx-loading';
import {AutoCompleteModule} from 'primeng/autocomplete';

@NgModule({
  imports: [
    CommonModule,
    AuthRoute,
    FormsModule,
    HttpClientModule,
    NgxPaginationModule,
    TranslateModule,
    StatisticModule,
    TabsModule.forRoot(),
    LoadingModule,
    AutoCompleteModule,
  ],
  declarations: [
    DashboardComponent,
    TaskListComponent,
    ScreenshotListComponent,
  ],
  providers: [
    LoginService,
    DashboardService,
  ]
})

export class DashboardModule {
}
