import {NgModule} from '@angular/core';
import {UsersFiltersComponent} from './filters/users/users.filters.component';
import {ProjectsFiltersComponent} from './filters/projects/projects.filters.component';
import {NgSelectModule} from '@ng-select/ng-select';
import {CommonModule} from '@angular/common';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {ProjectsService} from './pages/projects/projects.service';

@NgModule({
    imports: [
        CommonModule,
        FormsModule,
        ReactiveFormsModule,
        NgSelectModule
    ],
    declarations: [
        UsersFiltersComponent,
        ProjectsFiltersComponent
    ],
    exports: [
        CommonModule,
        FormsModule,
        NgSelectModule,
        ReactiveFormsModule,
        UsersFiltersComponent,
        ProjectsFiltersComponent
    ],
    providers: [
        ProjectsService
    ]
})

export class SharedModule {}
