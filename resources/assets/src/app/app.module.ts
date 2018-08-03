import { Location } from '@angular/common';
import {Router} from '@angular/router';
import {NgModule} from '@angular/core';
import {ModalModule} from 'ngx-bootstrap';
import {BrowserModule} from '@angular/platform-browser';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {TranslateModule, TranslateLoader, TranslateService} from '@ngx-translate/core';
import {TranslateHttpLoader} from '@ngx-translate/http-loader';
import {HttpClientModule, HttpClient} from '@angular/common/http';

import {AppRoutingModule} from './app-routing.module';
import {ApiModule} from './api/api.module';
import {GrowlModule} from 'primeng/growl';
import {SharedModule} from './shared.module';
import {MomentModule} from 'ngx-moment';

import {AppComponent} from './app.component';
import {DashboardComponent} from './dashboard/dashboard.component';
import {NavigationComponent} from './navigation/navigation.component';
import {IntegrationsComponent} from './pages/integrations/integrations.component';

import {AllowedActionsService} from './pages/roles/allowed-actions.service';
import {AttachedUsersService} from './pages/users/attached-users.service';
import {AttachedProjectService} from './pages/projects/attached-project.service';
import {ApiService} from './api/api.service';


export function HttpLoaderFactory(http: HttpClient) {
    return new TranslateHttpLoader(http,'/js/assets/lang/');
}


@NgModule({
    imports: [
        BrowserModule,
        FormsModule,
        ReactiveFormsModule,
        AppRoutingModule,
        ApiModule.forRoot(),
        ModalModule.forRoot(),
        GrowlModule,
        SharedModule,
        MomentModule,
        TranslateModule.forRoot({
            loader: {
                provide: TranslateLoader,
                useFactory: HttpLoaderFactory,
                deps: [HttpClient]
            }
        }),
    ],
    declarations: [
        AppComponent,
        DashboardComponent,
        NavigationComponent,
        IntegrationsComponent,
    ],
    providers: [
        AllowedActionsService,
        AttachedUsersService,
        AttachedProjectService,
    ],
    bootstrap: [AppComponent],
})
export class AppModule {
    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
        protected translate: TranslateService,
    ) {

        translate.setDefaultLang('en');

         // the lang to use, if the lang isn't available, it will use the current loader to get them
        translate.use('en');

        this.router.events.subscribe(this.checkPath.bind(this));
    }

    checkPath() {
        this.apiService.isAuthorized() ? this.checkAuthorizedPath() : this.checkGuestPath();
    }

    checkGuestPath() {
        const path = this.location.path();

        if (path.indexOf('/auth') === 0) {
            return;
        }

        this.location.go('/auth/login');
        this.router.navigateByUrl('/auth/login');
    }

    checkAuthorizedPath() {
        const path = this.location.path();

        if (path.indexOf('/auth') === 0) {
            this.location.go('/dashboard');
            this.router.navigateByUrl('/dashboard');
        }
    }
}
