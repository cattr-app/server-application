import {NgModule} from '@angular/core';
import {UsersFiltersComponent} from './filters/users/users.filters.component';
import {ProjectsFiltersComponent} from './filters/projects/projects.filters.component';
import {NgSelectModule} from '@ng-select/ng-select';
import {CommonModule} from '@angular/common';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {ProjectsService} from './pages/projects/projects.service';
import {DualListComponent, AngularDualListBoxModule} from 'angular-dual-listbox';
import {TranslateModule} from '@ngx-translate/core';
import { ScreenshotListComponent } from './screenshot-list/screenshot-list.component';
import { ScreenshotsService } from './pages/screenshots/screenshots.service';
import { ModalModule } from 'ngx-bootstrap';
import { LoadingModule } from 'ngx-loading';
import { DateRangeSelectorComponent } from './date-range-selector/date-range-selector.component';
import { UserSelectorComponent } from './user-selector/user-selector.component';
import { DpDatePickerModule } from 'ng2-date-picker';
import { UsersService } from './pages/users/users.service';
import { ProjectSelectorComponent } from './project-selector/project-selector.component';
import { ImageComponent } from './image/image.component';
import { RegistrationService } from './registration.service';
import { RouterModule } from '@angular/router';

@NgModule({
    imports: [
        CommonModule,
        FormsModule,
        ReactiveFormsModule,
        NgSelectModule,
        TranslateModule,
        AngularDualListBoxModule,
        ModalModule,
        LoadingModule,
        DpDatePickerModule,
        RouterModule,
    ],
    declarations: [
        UsersFiltersComponent,
        ProjectsFiltersComponent,
        ScreenshotListComponent,
        DateRangeSelectorComponent,
        UserSelectorComponent,
        ProjectSelectorComponent,
        ImageComponent,
    ],
    exports: [
        CommonModule,
        FormsModule,
        NgSelectModule,
        AngularDualListBoxModule,
        ReactiveFormsModule,
        UsersFiltersComponent,
        ProjectsFiltersComponent,
        ScreenshotListComponent,
        DualListComponent,
        DateRangeSelectorComponent,
        UserSelectorComponent,
        ProjectSelectorComponent,
        ImageComponent,
    ],
    providers: [
        ProjectsService,
        ScreenshotsService,
        UsersService,
        RegistrationService,
    ]
})

export class SharedModule {}
