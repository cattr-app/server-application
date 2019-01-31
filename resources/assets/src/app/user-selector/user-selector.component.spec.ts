import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ApiService } from '../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router } from '@angular/router';
import { APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy } from '@angular/common';
import { By } from '@angular/platform-browser';
import { UserSelectorComponent } from './user-selector.component';
import { AppRoutingModule } from '../app-routing.module';
import { AllowedActionsService } from '../pages/roles/allowed-actions.service';
import { TranslateService, TranslateModule, TranslateLoader, TranslateFakeLoader } from '@ngx-translate/core';
import { LocalStorage } from '../api/storage.model';
import { UsersService } from '../pages/users/users.service';
import {NgSelectModule} from '@ng-select/ng-select'

describe('User-selector component', () => {
    let component, fixture;

    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [UserSelectorComponent, ],
            schemas: [NO_ERRORS_SCHEMA],
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), NgSelectModule
            ],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                UsersService,
                AllowedActionsService,
                TranslateService,
            ]
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(UserSelectorComponent);
                component = fixture.debugElement.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', async(() => {
        expect(component).toBeTruthy();
    }));

    it('has selector', async(() => {
        const selector = fixture.debugElement.query(By.css("ng-select"));
        expect(selector).not.toBeNull();
    }));
});