import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {NgxPaginationModule} from 'ngx-pagination';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {AppRoutingModule} from '../../app-routing.module';
import {APP_BASE_HREF, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {loadAdminStorage, loadUserStorage} from '../../test-helper/test-helper';
import {Location} from '@angular/common';
import {Observable} from '../../../../../../node_modules/rxjs';
import {By} from '@angular/platform-browser';
import {SettingsComponent} from './settings.component';
import {ApiService} from '../../api/api.service';
import {FormsModule} from '@angular/forms';
import {NG_SELECT_DEFAULT_CONFIG, NgSelectModule} from '@ng-select/ng-select';
import {AppComponent} from '../../app.component';

describe('Settings component', () => {
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
        }),
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

  it('has a title', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).toContain('settings.title');
  }));

  it('has a save button', async(() => {
    console.log(component.languages);
    const el = fixture.debugElement.query(By.css('button[type="submit"]')).nativeElement;
    expect(el.innerHTML).toContain('control.save');
  }));
});
