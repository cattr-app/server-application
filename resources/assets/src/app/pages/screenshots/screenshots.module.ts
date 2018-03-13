import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './screenshots-routing.module';
import {LoginService} from "../../auth/login/login.service";

import {ScreenshotsCreateComponent} from './create/screenshots.create.component';
import {ScreenshotsEditComponent} from './edit/screenshots.edit.component';
import {ScreenshotsShowComponent} from './show/screenshots.show.component';
import {ScreenshotsListComponent} from './list/screenshots.list.component';

import {HttpClientModule} from '@angular/common/http';
import {ScreenshotsService} from "./screenshots.service";
import {NgxPaginationModule} from 'ngx-pagination';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule
    ],
    declarations: [
        ScreenshotsCreateComponent,
        ScreenshotsEditComponent,
        ScreenshotsListComponent,
        ScreenshotsShowComponent,
    ],
    providers: [
        LoginService,
        ScreenshotsService
    ]
})

export class ScreenshotsModule {
}
