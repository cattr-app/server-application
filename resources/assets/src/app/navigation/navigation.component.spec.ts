import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ApiService} from '../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {By} from '@angular/platform-browser';
import {LocalStorage} from '../api/storage.model';
import {NavigationComponent} from './navigation.component';
import {AppRoutingModule} from '../app-routing.module';
import {ProjectsService} from '../pages/projects/projects.service';
import {AllowedActionsService} from '../pages/roles/allowed-actions.service';
import {loadAdminStorage, loadUserStorage} from '../test-helper/test-helper';


describe('Navigation component(Admin)', () => {
  let component, fixture;
  let nav;

  beforeEach(async(() => {
    loadAdminStorage();
    TestBed.configureTestingModule({
      declarations: [NavigationComponent, ],
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
        ProjectsService
      ]
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(NavigationComponent);
      component = fixture.debugElement.componentInstance;
      component.setAuth(true);
      fixture.detectChanges();
      nav = fixture.debugElement.query(By.all()).nativeElement;
      console.log(nav);
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has menu item "dashboard"', async(() => {
    expect(nav.innerHTML).toContain('navigation.dashboard');
  }));

  it('has menu item "projects report"', async(() => {
    expect(nav.innerHTML).toContain('navigation.projectsreport');
  }));

  it('has menu item "projects"', async(() => {
    expect(nav.innerHTML).toContain('navigation.projects');
  }));

  it('has menu item "tasks"', async(() => {
    expect(nav.innerHTML).toContain('navigation.tasks');
  }));

  it('has menu item "users"', async(() => {
    expect(nav.innerHTML).toContain('navigation.users');
  }));

  it('has menu item "screenshots"', async(() => {
    expect(nav.innerHTML).toContain('navigation.screenshots');
  }));

  it('has menu item "integrations"', async(() => {
    expect(nav.innerHTML).toContain('navigation.integrations');
  }));

  it('has menu item "role"', async(() => {
    expect(nav.innerHTML).toContain('navigation.role');
  }));

  it('has menu item "settings"', async(() => {
    expect(nav.innerHTML).toContain('navigation.settings');
  }));

  it('has menu item "logout"', async(() => {
    expect(nav.innerHTML).toContain('navigation.logout');
  }));
});

describe('Navigation component(User)', () => {
  let component, fixture;
  let nav;

  beforeEach(async(() => {
    loadUserStorage();
    TestBed.configureTestingModule({
      declarations: [NavigationComponent, ],
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
        ProjectsService
      ]
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(NavigationComponent);
      component = fixture.debugElement.componentInstance;
      component.setAuth(true);
      fixture.detectChanges();
      nav = fixture.debugElement.query(By.all()).nativeElement;
      console.log(nav);
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has menu item "dashboard"', async(() => {
    expect(nav.innerHTML).toContain('navigation.dashboard');
  }));

  it('has menu item "projects report"', async(() => {
    expect(nav.innerHTML).toContain('navigation.projectsreport');
  }));

  it('has menu item "projects"', async(() => {
    expect(nav.innerHTML).toContain('navigation.projects');
  }));

  it('has menu item "tasks"', async(() => {
    expect(nav.innerHTML).toContain('navigation.tasks');
  }));

  it('has menu item "users"', async(() => {
    expect(nav.innerHTML).toContain('navigation.users');
  }));

  it('has menu item "screenshots"', async(() => {
    expect(nav.innerHTML).toContain('navigation.screenshots');
  }));

  it('has menu item "integrations"', async(() => {
    expect(nav.innerHTML).toContain('navigation.integrations');
  }));

  it('has not menu item "role"', async(() => {
    expect(nav.innerHTML).not.toContain('navigation.role');
  }));

  it('has menu item "settings"', async(() => {
    expect(nav.innerHTML).toContain('navigation.settings');
  }));

  it('has menu item "logout"', async(() => {
    expect(nav.innerHTML).toContain('navigation.logout');
  }));
});
