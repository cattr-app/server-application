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
import { UsersModule } from './pages/users/users.module';
import { UsersService } from './pages/users/users.service';

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
    ],
    declarations: [
        UsersFiltersComponent,
        ProjectsFiltersComponent,
        ScreenshotListComponent,
        DateRangeSelectorComponent,
        UserSelectorComponent,
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
    ],
    providers: [
        ProjectsService,
        ScreenshotsService,
        UsersService,
    ]
})

export class SharedModule {}
