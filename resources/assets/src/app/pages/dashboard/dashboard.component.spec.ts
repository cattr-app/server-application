import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DashboardComponent } from './dashboard.component';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {AppRoutingModule} from '../../app-routing.module';
import {Location, LocationStrategy, PathLocationStrategy, APP_BASE_HREF} from '@angular/common';
import {AllowedActionsService} from '../roles/allowed-actions.service';
import {By} from '@angular/platform-browser';
import {LocalStorage} from '../../api/storage.model';
import {loadAdminStorage, loadUserStorage} from '../../test-helper/test-helper';

describe('DashboardComponent(Manager)', () => {
  let fixture, component;

  beforeEach(async(() => {
    loadAdminStorage();
    TestBed.configureTestingModule({
      declarations: [DashboardComponent],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
      ],
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(DashboardComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
      // console.log(component.userIsManager);
      // console.log(component.allowedAction.can('dashboard/manager_access'));
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('should has "own" tab', () => {
    component.ngOnInit();
    component.ngAfterViewInit();
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('Own');
  });

  it('should has "Team" tab', () => {
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('Team');
  });
});

describe('DashboardComponent(User)', () => {
  let fixture, component;
  beforeEach(async(() => {
    loadUserStorage();

    TestBed.configureTestingModule({
      declarations: [DashboardComponent],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
      ],
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(DashboardComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
      // console.log(component.userIsManager);
      // console.log(component.allowedAction.can('dashboard/manager_access'));
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('should has not "own" tab', () => {
    component.ngOnInit();
    component.ngAfterViewInit();
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('Own');
  });

  it('should has not "Team" tab', () => {
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('Team');
  });
});
