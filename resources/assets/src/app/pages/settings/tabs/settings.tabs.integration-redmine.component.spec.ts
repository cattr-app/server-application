import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {NgxPaginationModule} from 'ngx-pagination';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {APP_BASE_HREF, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {loadAdminStorage, loadUserStorage, loadManagerStorage} from '../../../test-helper/test-helper';
import {Location} from '@angular/common';
import {Observable} from '../../../../../../../node_modules/rxjs';
import {IntegrationRedmineComponent} from './settings.tabs.integration-redmine.component';
import {ApiService} from '../../../api/api.service';
import {FormsModule} from '@angular/forms';
import {NG_SELECT_DEFAULT_CONFIG, NgSelectModule} from '@ng-select/ng-select';
import {AppComponent} from '../../../app.component';
import {TabsModule} from 'ngx-bootstrap/tabs';
import {ApiModule} from '../../../api/api.module';
import {By} from '@angular/platform-browser';


describe('Integration component in Settings (User)', () => {
    let component, fixture;
    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [IntegrationRedmineComponent, AppComponent],
            schemas: [NO_ERRORS_SCHEMA],
            imports: [
                NgSelectModule, FormsModule,
                NgxPaginationModule,
                TranslateModule.forRoot({
                    loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
                }), TabsModule.forRoot(), ApiModule
            ],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                {provide: Router, useClass: AppRoutingModule},
                Location,
                {provide: LocationStrategy, useClass: PathLocationStrategy},
                {provide: APP_BASE_HREF, useValue: '/'},
                {
                    provide: ActivatedRoute,
                    useValue: {
                        params: Observable.of({id: 123})
                    }
                },
            ]
        })
            .compileComponents().then(() => {
            loadUserStorage();
            fixture = TestBed.createComponent(IntegrationRedmineComponent);
            component = fixture.debugElement.componentInstance;
            fixture.detectChanges();
        });
    }));

    it('should be created', async(() => {
        expect(component).toBeTruthy();
    }));

    it('has a Redmine URL', async(() => {
        const input = fixture.debugElement.query(By.css('input[name=\'redmine-url\'][type=\'text\']'));
        expect(input).not.toBeNull();
        const divRow = input.parent.parent;
        expect(divRow.query(By.css('label')).nativeElement.innerHTML).toContain('integration.redmine.apiurl');
    }));

    it('has Redmine API key', async(() => {
        const input = fixture.debugElement.query(By.css('input[name=\'redmine-api-key\'][type=\'text\']'));
        expect(input).not.toBeNull();
        const divRow = input.parent.parent;
        expect(divRow.query(By.css('label')).nativeElement.innerHTML).toContain('integration.redmine.apikey');
    }));

    it('has a Redmine statuses', async(() => {
        const el = fixture.debugElement.query(By.css('form')).nativeElement;
        console.log(this.redmineStatuses);
        expect(el.innerHTML).toContain('integration.redmine.statuses');
    }));

    it('has a Redmine priorities', async(() => {
        const el = fixture.debugElement.query(By.css('form')).nativeElement;
        expect(el.innerHTML).toContain('integration.redmine.priorities');
    }));

    it('has a submit button', async(() => {
        const input = fixture.debugElement.query(By.css('[type=\'submit\']')).nativeElement;
        expect(input).not.toBeNull();
    }));
});

describe('Integration component in Settings (Manager)', () => {
    let component, fixture;
    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [IntegrationRedmineComponent, AppComponent],
            schemas: [NO_ERRORS_SCHEMA],
            imports: [
                NgSelectModule, FormsModule,
                NgxPaginationModule,
                TranslateModule.forRoot({
                    loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
                }), TabsModule.forRoot(),
            ],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                {provide: Router, useClass: AppRoutingModule},
                Location,
                {provide: LocationStrategy, useClass: PathLocationStrategy},
                {provide: APP_BASE_HREF, useValue: '/'},
                {
                    provide: ActivatedRoute,
                    useValue: {
                        params: Observable.of({id: 123})
                    }
                },
            ]
        })
            .compileComponents().then(() => {
            loadManagerStorage();
            fixture = TestBed.createComponent(IntegrationRedmineComponent);
            component = fixture.debugElement.componentInstance;
            fixture.detectChanges();
        });
    }));

    it('should be created', async(() => {
        expect(component).toBeTruthy();
    }));

    it('has a Redmine URL', async(() => {
        const input = fixture.debugElement.query(By.css('input[name=\'redmine-url\'][type=\'text\']'));
        expect(input).not.toBeNull();
        const divRow = input.parent.parent;
        expect(divRow.query(By.css('label')).nativeElement.innerHTML).toContain('integration.redmine.apiurl');
    }));

    it('has Redmine API key', async(() => {
        const input = fixture.debugElement.query(By.css('input[name=\'redmine-api-key\'][type=\'text\']'));
        expect(input).not.toBeNull();
        const divRow = input.parent.parent;
        expect(divRow.query(By.css('label')).nativeElement.innerHTML).toContain('integration.redmine.apikey');
    }));

    it('has a Redmine statuses', async(() => {
        const el = fixture.debugElement.query(By.css('form')).nativeElement;
        console.log(this.redmineStatuses);
        expect(el.innerHTML).toContain('integration.redmine.statuses');
    }));

    it('has a Redmine priorities', async(() => {
        const el = fixture.debugElement.query(By.css('form')).nativeElement;
        expect(el.innerHTML).toContain('integration.redmine.priorities');
    }));

    it('has a submit button', async(() => {
        const input = fixture.debugElement.query(By.css('[type=\'submit\']')).nativeElement;
        expect(input).not.toBeNull();
    }));
});

describe('Integration component in Settings (Admin)', () => {
    let component, fixture;
    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [IntegrationRedmineComponent, AppComponent],
            schemas: [NO_ERRORS_SCHEMA],
            imports: [
                NgSelectModule, FormsModule,
                NgxPaginationModule,
                TranslateModule.forRoot({
                    loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
                }), TabsModule.forRoot(),
            ],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                {provide: Router, useClass: AppRoutingModule},
                Location,
                {provide: LocationStrategy, useClass: PathLocationStrategy},
                {provide: APP_BASE_HREF, useValue: '/'},
                {
                    provide: ActivatedRoute,
                    useValue: {
                        params: Observable.of({id: 123})
                    }
                },
            ]
        })
            .compileComponents().then(() => {
            loadAdminStorage();
            fixture = TestBed.createComponent(IntegrationRedmineComponent);
            component = fixture.debugElement.componentInstance;
            fixture.detectChanges();
        });
    }));

    it('should be created', async(() => {
        expect(component).toBeTruthy();
    }));

    it('has a Redmine URL', async(() => {
        const input = fixture.debugElement.query(By.css('input[name=\'redmine-url\'][type=\'text\']'));
        expect(input).not.toBeNull();
        const divRow = input.parent.parent;
        expect(divRow.query(By.css('label')).nativeElement.innerHTML).toContain('integration.redmine.apiurl');
    }));

    it('has Redmine API key', async(() => {
        const input = fixture.debugElement.query(By.css('input[name=\'redmine-api-key\'][type=\'text\']'));
        expect(input).not.toBeNull();
        const divRow = input.parent.parent;
        expect(divRow.query(By.css('label')).nativeElement.innerHTML).toContain('integration.redmine.apikey');
    }));

    it('has a Redmine statuses', async(() => {
        const el = fixture.debugElement.query(By.css('form')).nativeElement;
        console.log(this.redmineStatuses);
        expect(el.innerHTML).toContain('integration.redmine.statuses');
    }));

    it('has a Redmine priorities', async(() => {
        const el = fixture.debugElement.query(By.css('form')).nativeElement;
        expect(el.innerHTML).toContain('integration.redmine.priorities');
    }));

    it('has a submit button', async(() => {
        const input = fixture.debugElement.query(By.css('[type=\'submit\']')).nativeElement;
        expect(input).not.toBeNull();
    }));

});