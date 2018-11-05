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
import {GeneralComponent} from './settings.tabs.general.component'
import {ApiService} from '../../../api/api.service';
import {FormsModule} from '@angular/forms';
import {NG_SELECT_DEFAULT_CONFIG, NgSelectModule} from '@ng-select/ng-select';
import {AppComponent} from '../../../app.component';
import {TabsModule} from 'ngx-bootstrap/tabs';
import {By} from '@angular/platform-browser';

describe('General component in Settings (User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [GeneralComponent, AppComponent],
      schemas: [NO_ERRORS_SCHEMA],
      imports: [
        NgSelectModule, FormsModule,
        NgxPaginationModule,
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot()
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
      fixture = TestBed.createComponent(GeneralComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has language settings', async(() => {
    const el = fixture.debugElement.query(By.css("ng-select")).nativeElement;
    expect(el).not.toBeNull();
    expect(el.innerHTML).toContain("settings.select-lang");
  }));

  it('has submit button', async(() => {
    const el = fixture.debugElement.query(By.css("[type='submit']")).nativeElement;
    expect(el.innerHTML).not.toBeNull();
  }));
});

describe('General component in Settings (Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [GeneralComponent, AppComponent],
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
      fixture = TestBed.createComponent(GeneralComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has language settings', async(() => {
    const el = fixture.debugElement.query(By.css("ng-select")).nativeElement;
    expect(el).not.toBeNull();
    expect(el.innerHTML).toContain("settings.select-lang");
  }));

  it('has submit button', async(() => {
    const el = fixture.debugElement.query(By.css("[type='submit']")).nativeElement;
    expect(el.innerHTML).not.toBeNull();
  }));
});

describe('General component in Settings (Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [GeneralComponent, AppComponent],
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
      fixture = TestBed.createComponent(GeneralComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has language settings', async(() => {
    const el = fixture.debugElement.query(By.css("ng-select")).nativeElement;
    expect(el).not.toBeNull();
    expect(el.innerHTML).toContain("settings.select-lang");
  }));

  it('has submit button', async(() => {
    const el = fixture.debugElement.query(By.css("[type='submit']")).nativeElement;
    expect(el.innerHTML).not.toBeNull();
  }));
});