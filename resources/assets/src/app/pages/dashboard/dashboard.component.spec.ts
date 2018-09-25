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

describe('DashboardComponent', () => {
  let fixture, component;
  const localStorage = LocalStorage.getStorage();

  beforeEach(async(() => {
    localStorage.set('allowed_actions', [{
      'object': 'dashboard',
      'action': 'manager_access',
      'name': 'Dashboard manager access'
    }, {
      'object': 'time-intervals',
      'action': 'full_access',
      'name': 'Time intervals full access'
    }]);
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
      // console.log(component.userIsManager);
      // console.log(component.allowedAction.can('dashboard/manager_access'));
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  // it('should has "own" tab', () => {
  //   component.ngOnInit();
  //   component.ngAfterViewInit();
  //   const el  = fixture.debugElement.query(By.all()).nativeElement;
  //   expect(el.innerHTML).toContain('Own');
  // });
  //
  // it('should has "Team" tab', () => {
  //   const el  = fixture.debugElement.query(By.all()).nativeElement;
  //   expect(el.innerHTML).toContain('Team');
  // });
});
