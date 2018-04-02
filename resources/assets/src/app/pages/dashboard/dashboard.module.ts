import { NgModule, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { AuthRoute } from './dashboard-routing.module';
import { LoginService } from "../../auth/login/login.service";

import { HttpClientModule } from '@angular/common/http';
import { NgxPaginationModule } from 'ngx-pagination';

import { DashboardComponent } from './dashboard.component';
import { TaskListComponent } from './tasklist/tasks.list.component';
import { ScreenshotListComponent } from './screenshotlist/screenshot.list.component';

@NgModule({
  imports: [
    CommonModule,
    AuthRoute,
    FormsModule,
    HttpClientModule,
    NgxPaginationModule
  ],
  declarations: [
    DashboardComponent,
    TaskListComponent,
    ScreenshotListComponent
  ],
  providers: [
    LoginService
  ]
})

export class DashboardModule {
}
