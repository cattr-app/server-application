import { Location } from '@angular/common';
import {Router} from '@angular/router';
import {NgModule} from '@angular/core';
import {ModalModule} from 'ngx-bootstrap';
import {BrowserModule} from '@angular/platform-browser';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';

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
        MomentModule
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
    bootstrap: [AppComponent]
})
export class AppModule {
    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
    ) {
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
