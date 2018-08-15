import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {AuthRoute} from './statistic-routing.module';
import {LoginService} from '../../auth/login/login.service';

import {StatisticTimeComponent} from './time/statistic.time.component';


import {UsersService} from '../users/users.service';


import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from '../../shared.module';
import {TranslateModule} from '@ngx-translate/core';


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
    ],
    declarations: [
        /**
         * @todo add here the other components, except for static for time usage
         */
        StatisticTimeComponent,
    ],
    providers: [
        LoginService,
        UsersService,
    ]
})

export class StatisticModule {
}
