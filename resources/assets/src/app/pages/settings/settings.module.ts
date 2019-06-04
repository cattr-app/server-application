import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {SettingsRoute} from './settings-routing.module';
import {LoginService} from '../../auth/login/login.service';
import {HttpClientModule} from '@angular/common/http';
import {NgxPaginationModule} from 'ngx-pagination';
import {SettingsComponent} from './settings.component';
import {GeneralComponent} from './tabs/settings.tabs.general.component';
import {IntegrationRedmineComponent} from './tabs/settings.tabs.integration-redmine.component';
import {IntegrationGitlabComponent} from './tabs/settings.tabs.integration-gitlab.component';
import {UserSettingsComponent} from './tabs/settings.tabs.user.component';
import {TimezonePickerModule} from 'ng2-timezone-selector';
import {TranslateModule} from '@ngx-translate/core';
import {StatisticModule} from '../statistic/statistic.module';
import {TabsModule, ModalModule} from 'ngx-bootstrap';
import {LoadingModule} from 'ngx-loading';
import {AutoCompleteModule} from 'primeng/autocomplete';
import {NgSelectModule} from '@ng-select/ng-select';
import {SharedModule} from '../../shared.module';
import {GrowlModule} from 'primeng/growl';
import {TooltipModule} from 'ngx-bootstrap';

@NgModule({
    imports: [
        CommonModule,
        SettingsRoute,
        FormsModule,
        HttpClientModule,
        NgxPaginationModule,
        TranslateModule,
        StatisticModule,
        TabsModule.forRoot(),
        LoadingModule,
        AutoCompleteModule,
        NgSelectModule,
        ModalModule,
        SharedModule,
        TimezonePickerModule,
        GrowlModule,
        TooltipModule.forRoot(),
    ],
    declarations: [
        SettingsComponent,
        GeneralComponent,
        IntegrationRedmineComponent,
        IntegrationGitlabComponent,
        UserSettingsComponent,
    ],
    providers: [
        LoginService,
    ]
})

export class SettingsModule {
}
