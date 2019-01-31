import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {By} from '@angular/platform-browser';
import {ProjectsFiltersComponent} from './projects.filters.component';
import {AppRoutingModule} from '../../app-routing.module';
import {AllowedActionsService} from '../../pages/roles/allowed-actions.service';
import { ProjectsService } from '../../pages/projects/projects.service';
import { TranslateModule, TranslateLoader, TranslateFakeLoader } from '@ngx-translate/core';
import { AttachedProjectService } from '../../pages/projects/attached-project.service';
import {LocalStorage} from '../../api/storage.model';
import {NgSelectModule} from '@ng-select/ng-select';
import {loadUserStorage} from '../../test-helper/test-helper'

describe('Projects filter component (has not projects)', () => {
  let component, fixture;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ProjectsFiltersComponent, ],
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
        ProjectsService,
        AttachedProjectService,
    ]
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(ProjectsFiltersComponent);
      component = fixture.debugElement.componentInstance;
      LocalStorage.getStorage().clear();      
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


describe('Projects filter component (has projects)', () => {
  let component, fixture;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ProjectsFiltersComponent, ],
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
        ProjectsService,
        AttachedProjectService,
    ]
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(ProjectsFiltersComponent);
      component = fixture.debugElement.componentInstance;
      loadUserStorage();      
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
