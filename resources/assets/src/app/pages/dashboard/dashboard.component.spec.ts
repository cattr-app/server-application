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
import {loadAdminStorage, loadUserStorage, loadManagerStorage} from '../../test-helper/test-helper';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import { TabsModule } from 'ngx-bootstrap/tabs';

describe('DashboardComponent(Admin)', () => {
  let fixture, component;
  
  beforeEach(async(() => {
    loadAdminStorage();
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot(),
      ],
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
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('should has "own" tab', () => {
    component.ngOnInit();
    component.ngAfterViewInit();
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.own');
  });

  it('should has "Team" tab', () => {
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.team');
  });

  it('load saved in local storage tab', async(() => {
    LocalStorage.getStorage().set("settings-tab", "Team");
    fixture.detectChanges();
    const el = fixture.debugElement.query(By.css("a.nav-link.active")).nativeElement;
    expect(el.innerHTML).toContain("control.team");
  }));

  it('tabs is clickable', async(() => {
    LocalStorage.getStorage().set("dashboard-tab", "Own");
    fixture.detectChanges();
    let allElements = fixture.debugElement.queryAll(By.css("a.nav-link"));
    let noActiveElements = allElements.filter(el => !el.nativeElement.className.includes("active"));
    let el;
    while (noActiveElements.length > 0) {
      el = noActiveElements.shift();
      el.nativeElement.click();
      fixture.detectChanges();
      expect(fixture.debugElement.query(By.css("a.nav-link.active")).nativeElement.innerHTML).toContain(el.nativeElement.innerHTML);
    }
  }));
});

describe('DashboardComponent(Manager)', () => {
  let fixture, component;
  
  beforeEach(async(() => {
    loadManagerStorage();
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot(),
      ],
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
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('should has "own" tab', () => {
    component.ngOnInit();
    component.ngAfterViewInit();
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.own');
  });

  it('should has "Team" tab', () => {
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.team');
  });

  it('load saved in local storage tab', async(() => {
    LocalStorage.getStorage().set("settings-tab", "Team");
    fixture.detectChanges();
    const el = fixture.debugElement.query(By.css("a.nav-link.active")).nativeElement;
    expect(el.innerHTML).toContain("control.team");
  }));

  it('tabs is clickable', async(() => {
    LocalStorage.getStorage().set("dashboard-tab", "Own");
    fixture.detectChanges();
    let allElements = fixture.debugElement.queryAll(By.css("a.nav-link"));
    let noActiveElements = allElements.filter(el => !el.nativeElement.className.includes("active"));
    let el;
    while (noActiveElements.length > 0) {
      el = noActiveElements.shift();
      el.nativeElement.click();
      fixture.detectChanges();
      expect(fixture.debugElement.query(By.css("a.nav-link.active")).nativeElement.innerHTML).toContain(el.nativeElement.innerHTML);
    }
  }));
});

describe('DashboardComponent(User)', () => {
  let fixture, component;
  beforeEach(async(() => {
    loadUserStorage();

    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot(),
      ],
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
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('should has not "own" tab', () => {
    component.ngOnInit();
    component.ngAfterViewInit();
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('control.own');
  });

  it('should has not "team" statistic tab', () => {
    const el  = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('control.team');
  });
});
