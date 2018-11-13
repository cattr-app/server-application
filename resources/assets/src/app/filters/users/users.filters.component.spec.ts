import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {By} from '@angular/platform-browser';
import {UsersFiltersComponent} from './users.filters.component';
import {AppRoutingModule} from '../../app-routing.module';
import {AllowedActionsService} from '../../pages/roles/allowed-actions.service';
import { TranslateModule, TranslateLoader, TranslateFakeLoader } from '@ngx-translate/core';
import { AttachedUsersService } from '../../pages/users/attached-users.service';
import {LocalStorage} from '../../api/storage.model';
import {NgSelectModule} from '@ng-select/ng-select';
import {loadAdminStorage, loadUserStorage, loadManagerStorage} from '../../test-helper/test-helper'
import { UsersService } from '../../pages/users/users.service';

describe('User filter component (User)', () => {
  let component, fixture;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [UsersFiltersComponent, ],
      imports: [
        TranslateModule.forRoot({
            loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }), NgSelectModule
      ],
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
        UsersService,
        AttachedUsersService,
    ]
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(UsersFiltersComponent);
      component = fixture.debugElement.componentInstance;
      loadUserStorage();
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has not selector projects', async(() => {
    const selector = fixture.debugElement.query(By.css("ng-select"));
    expect(selector).toBeNull();
  }));
  
});


describe('User filter component (Manager)', () => {
    let component, fixture;
  
    beforeEach(async(() => {
      TestBed.configureTestingModule({
        declarations: [UsersFiltersComponent, ],
        imports: [
          TranslateModule.forRoot({
              loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
          }), NgSelectModule
        ],
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
          UsersService,
          AttachedUsersService,
      ]
      })
        .compileComponents().then(() => {
        fixture = TestBed.createComponent(UsersFiltersComponent);
        component = fixture.debugElement.componentInstance;
        loadManagerStorage();      
        fixture.detectChanges();
      });
    }));
  
    it('should be created', async(() => {
      expect(component).toBeTruthy();
    }));
  
    it('has selector projects', async(() => {
      const selector = fixture.debugElement.query(By.css("ng-select"));
      expect(selector).not.toBeNull();
    }));
  });


describe('User filter component (Admin)', () => {
  let component, fixture;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [UsersFiltersComponent, ],
      imports: [
        TranslateModule.forRoot({
            loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }), NgSelectModule
      ],
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
        UsersService,
        AttachedUsersService,
    ]
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(UsersFiltersComponent);
      component = fixture.debugElement.componentInstance;
      loadAdminStorage();      
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has selector projects', async(() => {
    const selector = fixture.debugElement.query(By.css("ng-select"));
    expect(selector).not.toBeNull();
  }));
});
