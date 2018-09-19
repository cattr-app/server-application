import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './screenshots-routing.module';
import {LoginService} from '../../auth/login/login.service';

import {ScreenshotsCreateComponent} from './create/screenshots.create.component';
import {ScreenshotsEditComponent} from './edit/screenshots.edit.component';
import {ScreenshotsShowComponent} from './show/screenshots.show.component';
import {ScreenshotsListComponent} from './list/screenshots.list.component';

import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';
import {TranslateModule} from '@ngx-translate/core';
import { DpDatePickerModule } from 'ng2-date-picker';

@NgModule({
    imports: [
        CommonModule,
        AuthRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        GrowlModule,
        SharedModule,
        TranslateModule,
        DpDatePickerModule,
    ],
    declarations: [
        ScreenshotsCreateComponent,
        ScreenshotsEditComponent,
        ScreenshotsListComponent,
        ScreenshotsShowComponent,
    ],
    providers: [
        LoginService,
    ]
})

export class ScreenshotsModule {
}
