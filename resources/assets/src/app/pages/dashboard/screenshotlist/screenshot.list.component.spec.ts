import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardService } from '../dashboard.service';
import { ScreenshotListComponent } from './screenshot.list.component';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ApiService } from '../../../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router } from '@angular/router';
import { AppRoutingModule } from '../../../app-routing.module';
import { Location, LocationStrategy, PathLocationStrategy, APP_BASE_HREF } from '@angular/common';
import { loadAdminStorage, loadUserStorage, loadManagerStorage } from '../../../test-helper/test-helper';
import { TranslateFakeLoader, TranslateLoader, TranslateModule } from '@ngx-translate/core';
import { TabsModule } from 'ngx-bootstrap/tabs';
import { NgxPaginationModule } from 'ngx-pagination';
import { ModalModule, BsModalService, ComponentLoaderFactory, PositioningService } from 'ngx-bootstrap';
import { ScreenshotsService } from '../../screenshots/screenshots.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { ProjectsService } from '../../projects/projects.service';
import { TasksService } from '../../tasks/tasks.service';


describe('Dashboard ScreenshotListComponent (Admin, has screenshots)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadAdminStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, ModalModule,
            ],
            declarations: [ScreenshotListComponent,],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                DashboardService,
                ScreenshotsService,
                TimeIntervalsService,
                ProjectsService,
                TasksService,
                BsModalService,
                ApiService,
                ComponentLoaderFactory,
                PositioningService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(ScreenshotListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        console.log(fixture.debugElement.nativeElement);
        expect(component).toBeTruthy();
    });


});

describe('Dashboard ScreenshotListComponent (Admin, has not screenshots)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadAdminStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, ModalModule,
            ],
            declarations: [ScreenshotListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                DashboardService,
                ScreenshotsService,
                TimeIntervalsService,
                ProjectsService,
                TasksService,
                BsModalService,
                ApiService,
                ComponentLoaderFactory,
                PositioningService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(ScreenshotListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });
});

describe('Dashboard ScreenshotListComponent(Manager, has screenshots)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadManagerStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, ModalModule,
            ],
            declarations: [ScreenshotListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                DashboardService,
                ScreenshotsService,
                TimeIntervalsService,
                ProjectsService,
                TasksService,
                BsModalService,
                ApiService,
                ComponentLoaderFactory,
                PositioningService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(ScreenshotListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });
});

describe('Dashboard ScreenshotListComponent(Manager, has not screenshots)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadManagerStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, ModalModule,
            ],
            declarations: [ScreenshotListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                DashboardService,
                ScreenshotsService,
                TimeIntervalsService,
                ProjectsService,
                TasksService,
                BsModalService,
                ApiService,
                ComponentLoaderFactory,
                PositioningService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(ScreenshotListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });
});

describe('Dashboard ScreenshotListComponent(User, has screenshots)', () => {
    let fixture, component;
    beforeEach(async(() => {
        loadUserStorage();

        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, ModalModule,
            ],
            declarations: [ScreenshotListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                DashboardService,
                ScreenshotsService,
                TimeIntervalsService,
                ProjectsService,
                TasksService,
                BsModalService,
                ComponentLoaderFactory,
                PositioningService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(ScreenshotListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });
});


describe('Dashboard ScreenshotListComponent(User, has not screenshots)', () => {
    let fixture, component;
    beforeEach(async(() => {
        loadUserStorage();

        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, ModalModule,
            ],
            declarations: [ScreenshotListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                DashboardService,
                ScreenshotsService,
                TimeIntervalsService,
                ProjectsService,
                TasksService,
                BsModalService,
                ComponentLoaderFactory,
                PositioningService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(ScreenshotListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });
});