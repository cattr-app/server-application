import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {NgxPaginationModule} from 'ngx-pagination';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {AppRoutingModule} from '../../app-routing.module';
import {APP_BASE_HREF, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {loadAdminStorage, loadUserStorage, loadManagerStorage} from '../../test-helper/test-helper';
import {Location} from '@angular/common';
import {Observable} from '../../../../../../node_modules/rxjs';
import {By} from '@angular/platform-browser';
import {SettingsComponent} from './settings.component';
import {ApiService} from '../../api/api.service';
import {FormsModule} from '@angular/forms';
import {NG_SELECT_DEFAULT_CONFIG, NgSelectModule} from '@ng-select/ng-select';
import {AppComponent} from '../../app.component';
import {TabsModule} from 'ngx-bootstrap/tabs';
import {LocalStorage} from '../../api/storage.model';

describe('Settings component (User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [SettingsComponent, AppComponent],
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
      fixture = TestBed.createComponent(SettingsComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has a General tab', async(() => {
  const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('settings.general');
  }));

  it('has a Redmine Integration tab', async(() => {
    const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('integrationRedmine.redmine.title');
  }));

  it('has a Account tab', async(() => {
    const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('settings.user');
  }));

  it('load saved in local storage tab', async(() => {
    LocalStorage.getStorage().set("settings-tab", "Account");
    fixture.detectChanges();
    const el = fixture.debugElement.query(By.css("a.nav-link.active")).nativeElement;
    expect(el.innerHTML).toContain("settings.user");
  }));

  it('tabs is clickable', async(() => {
    LocalStorage.getStorage().set("settings-tab", "General");
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

describe('Settings component (Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [SettingsComponent, AppComponent],
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
      fixture = TestBed.createComponent(SettingsComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has a General tab', async(() => {
  const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('settings.general');
  }));

  it('has a Redmine Integration tab', async(() => {
    const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('integrationRedmine.redmine.title');
  }));

  it('has a Account tab', async(() => {
    const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('settings.user');
  }));

  it('load saved in local storage tab', async(() => {
    LocalStorage.getStorage().set("settings-tab", "Account");
    fixture.detectChanges();
    const el = fixture.debugElement.query(By.css("a.nav-link.active")).nativeElement;
    expect(el.innerHTML).toContain("settings.user");
  }));

  it('tabs is clickable', async(() => {
    LocalStorage.getStorage().set("settings-tab", "General");
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

describe('Settings component (Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [SettingsComponent, AppComponent],
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
      fixture = TestBed.createComponent(SettingsComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has a General tab', async(() => {
  const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('settings.general');
  }));

  it('has a Redmine Integration tab', async(() => {
    const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('integrationRedmine.redmine.title');
  }));

  it('has a Account tab', async(() => {
    const el = fixture.debugElement.query(By.css(".tab-container")).nativeElement;
    expect(el.innerHTML).toContain('settings.user');
  }));

  it('load saved in local storage tab', async(() => {
    LocalStorage.getStorage().set("settings-tab", "Account");
    fixture.detectChanges();
    const el = fixture.debugElement.query(By.css("a.nav-link.active")).nativeElement;
    expect(el.innerHTML).toContain("settings.user");
  }));

  it('tabs is clickable', async(() => {
    LocalStorage.getStorage().set("settings-tab", "General");
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