import {NgModule, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './projects-routing.module';
import {LoginService} from "../../auth/login/login.service";

import {ProjectsCreateComponent} from './create/projects.create.component';
import {ProjectsEditComponent} from './edit/projects.edit.component';
import {ProjectsShowComponent} from './show/projects.show.component';
import {ProjectsListComponent} from './list/projects.list.component';

import {HttpClientModule} from '@angular/common/http';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule
    ],
    declarations: [
        ProjectsCreateComponent,
        ProjectsEditComponent,
        ProjectsListComponent,
        ProjectsShowComponent,
    ],
    providers: [
        LoginService
    ]
})

export class ProjectsModule {
}
