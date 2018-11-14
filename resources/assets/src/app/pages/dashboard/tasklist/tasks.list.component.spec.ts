/*

    TODO:
    
    1) Checking clickable tasks, projects
    2) Checking correct calc time for task
    3) Checking pagination

    FIXME:

    1) API service in tests

*/

import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardService } from '../dashboard.service';
import { TaskListComponent } from './tasks.list.component';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ApiService } from '../../../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router } from '@angular/router';
import { AppRoutingModule } from '../../../app-routing.module';
import { Location, LocationStrategy, PathLocationStrategy, APP_BASE_HREF } from '@angular/common';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { loadAdminStorage, loadUserStorage, loadManagerStorage } from '../../../test-helper/test-helper';
import { TranslateFakeLoader, TranslateLoader, TranslateModule } from '@ngx-translate/core';
import { TabsModule } from 'ngx-bootstrap/tabs';
import { NgxPaginationModule } from 'ngx-pagination';
import {
    HttpClientTestingModule,
    HttpTestingController
  } from '@angular/common/http/testing';

describe('Dashboard tasklist component (Admin, has tasks)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadAdminStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule,
            ],
            declarations: [TaskListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                DashboardService,
                ApiService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(TaskListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });

    xit('has tasks', () => {
        expect(fixture.debugElement.nativeElement.innerHTML).not.toContain("dashboard.no-task");
        expect(component.filteredItems.length).toBeGreaterThan(0);
    });
});

describe('Dashboard tasklist component (Admin, has not tasks)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadAdminStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule,
            ],
            declarations: [TaskListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                DashboardService,
                ApiService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(TaskListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });

    it('has not tasks', () => {
        expect(fixture.debugElement.nativeElement.innerHTML).toContain("dashboard.no-task");
        expect(component.filteredItems.length).toBe(0);
        expect(component.totalTimeStr).toBe("00:00")
    });
});

describe('Dashboard tasklist component(Manager, has tasks)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadManagerStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule,
            ],
            declarations: [TaskListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                DashboardService,
                ApiService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(TaskListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });

    xit('has tasks', () => {
        expect(fixture.debugElement.nativeElement.innerHTML).not.toContain("dashboard.no-task");
        expect(component.filteredItems.length).toBeGreaterThan(0);
    });
});

describe('Dashboard tasklist component(Manager, has not tasks)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadManagerStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule,
            ],
            declarations: [TaskListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                DashboardService,
                ApiService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(TaskListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });

    it('has not tasks', () => {
        expect(fixture.debugElement.nativeElement.innerHTML).toContain("dashboard.no-task");
        expect(component.filteredItems.length).toBe(0);
        expect(component.totalTimeStr).toBe("00:00")
    });
});

describe('Dashboard tasklist component(User, has tasks)', () => {
    let fixture, component;
    beforeEach(async(() => {
        loadUserStorage();

        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, HttpClientTestingModule
            ],
            declarations: [TaskListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                DashboardService,
                ApiService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(TaskListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });

    it('has tasks', () => {
        console.log(component);
        expect(fixture.debugElement.nativeElement.innerHTML).not.toContain("dashboard.no-task");
        expect(component.filteredItems.length).toBeGreaterThan(0);
    });
});


describe('Dashboard tasklist component(User, has not tasks)', () => {
    let fixture, component;
    beforeEach(async(() => {
        loadUserStorage();

        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule,
            ],
            declarations: [TaskListComponent],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                DashboardService,
                ApiService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(TaskListComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        expect(component).toBeTruthy();
    });

    it('has not tasks', () => {
        expect(fixture.debugElement.nativeElement.innerHTML).toContain("dashboard.no-task");
        expect(component.filteredItems.length).toBe(0);
        expect(component.totalTimeStr).toBe("00:00")
    });
});