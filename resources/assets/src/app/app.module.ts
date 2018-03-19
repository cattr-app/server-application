import {NgModule} from '@angular/core';
import {ModalModule} from 'ngx-bootstrap';
import {BrowserModule} from '@angular/platform-browser';
import {FormsModule} from '@angular/forms';

import {AppRoutingModule} from './app-routing.module';

import {AppComponent} from './app.component';
import {DashboardComponent} from './dashboard/dashboard.component';
import {ApiModule} from './api/api.module';
import {NavigationComponent} from './navigation/navigation.component';
import {ApiService} from './api/api.service';
import {Router} from '@angular/router';
import { Location } from '@angular/common';
import {IntegrationsComponent} from "./pages/integrations/integrations.component";

@NgModule({
    imports: [
        BrowserModule,
        FormsModule,
        AppRoutingModule,
        ApiModule.forRoot(),
        ModalModule.forRoot()
    ],
    declarations: [
        AppComponent,
        DashboardComponent,
        NavigationComponent,
        IntegrationsComponent,
    ],
    providers: [],
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
