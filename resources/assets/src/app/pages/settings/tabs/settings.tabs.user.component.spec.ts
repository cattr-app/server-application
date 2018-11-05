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
import {UserSettingsComponent} from './settings.tabs.user.component'
import {ApiService} from '../../../api/api.service';
import {FormsModule} from '@angular/forms';
import {NG_SELECT_DEFAULT_CONFIG, NgSelectModule} from '@ng-select/ng-select';
import {AppComponent} from '../../../app.component';
import {TabsModule} from 'ngx-bootstrap/tabs';
import { By } from '@angular/platform-browser';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { ProjectsService } from '../../projects/projects.service';
import { RolesService } from '../../roles/roles.service';
import { UsersService } from '../../users/users.service';

describe('UserSettingsComponent component in Settings (User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [UserSettingsComponent, AppComponent],
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
        AllowedActionsService,
        ProjectsService,
        RolesService,
        UsersService,
      ]
    })
      .compileComponents().then(() => {
      loadUserStorage();
      fixture = TestBed.createComponent(UserSettingsComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has a fullname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-full-name'][type='text'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.fullname');
  }));
  
  it('has a firstname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-first-name'][type='text']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.firstname');
  }));

  it('has a lastname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-last-name'][type='text']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.lastname');
  }));

  it('has a email', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-email'][type='text'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.email');
  }));
  
  it('has a password', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-password'][type='password']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.password'); 
   }));

  it('has a avatar', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-avatar'][type='file']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.avatar'); 
  }));

  it('has a URL', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-url'][type='text']"));
    expect(input).toBeNull();
  }));
  
  it('has a user active', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-active'][required]"));
    expect(input).toBeNull();
  }));

  it('has a role', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-role-id'][required]"));
    expect(input).toBeNull();
  }));

  it('has a screenshots-active', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-screenshots-active'][required]"));
    expect(input).toBeNull();
  }));
  
  it('has a manual-time', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-manual-time'][required]"));
    expect(input).toBeNull();
  }));

  it('has a screenshots interval', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-screenshots-interval'][type='number'][required]"));
    expect(input).toBeNull();
  }));

  it('has a user inactive time', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-computer-time-popup'][type='number'][required]"));
    expect(input).toBeNull();
  }));

  it('has a timezone', async(() => {
    const input = fixture.debugElement.query(By.css("ng2-timezone-picker"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.timezone');
  }));

  it('has a submit', async(() => {
    const input = fixture.debugElement.query(By.css("[type='submit']")).nativeElement;
    expect(input).not.toBeNull();
  }));
});

describe('UserSettingsComponent component in Settings (Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [UserSettingsComponent, AppComponent],
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
        AllowedActionsService,
        ProjectsService,
        RolesService,
        UsersService,
      ]
    })
      .compileComponents().then(() => {
      loadManagerStorage();
      fixture = TestBed.createComponent(UserSettingsComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has a fullname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-full-name'][type='text'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.fullname');
  }));
  
  it('has a firstname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-first-name'][type='text']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.firstname');
  }));

  it('has a lastname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-last-name'][type='text']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.lastname');
  }));

  it('has a email', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-email'][type='text'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.email');
  }));
  
  it('has a password', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-password'][type='password']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.password'); 
   }));

  it('has a avatar', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-avatar'][type='file']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.avatar'); 
  }));

  it('has a URL', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-url'][type='text']"));
    expect(input).toBeNull();
  }));
  
  it('has a user active', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-active'][required]"));
    expect(input).toBeNull();
  }));

  it('has a role', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-role-id'][required]"));
    expect(input).toBeNull();
  }));

  it('has a screenshots-active', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-screenshots-active'][required]"));
    expect(input).toBeNull();
  }));
  
  it('has a manual-time', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-manual-time'][required]"));
    expect(input).toBeNull();
  }));

  it('has a screenshots interval', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-screenshots-interval'][type='number'][required]"));
    expect(input).toBeNull();
  }));

  it('has a user inactive time', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-computer-time-popup'][type='number'][required]"));
    expect(input).toBeNull();
  }));

  it('has a timezone', async(() => {
    const input = fixture.debugElement.query(By.css("ng2-timezone-picker"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.timezone');
  }));

  it('has a submit', async(() => {
    const input = fixture.debugElement.query(By.css("[type='submit']")).nativeElement;
    expect(input).not.toBeNull();
  }));
});

describe('UserSettingsComponent component in Settings (Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [UserSettingsComponent, AppComponent],
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
        AllowedActionsService,
        ProjectsService,
        RolesService,
        UsersService,
      ]
    })
      .compileComponents().then(() => {
      loadAdminStorage();
      fixture = TestBed.createComponent(UserSettingsComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has a fullname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-full-name'][type='text'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.fullname');
  }));
  
  it('has a firstname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-first-name'][type='text']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.firstname');
  }));

  it('has a lastname', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-last-name'][type='text']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.lastname');
  }));

  it('has a email', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-email'][type='text'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.email');
  }));
  
  it('has a password', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-password'][type='password']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.password'); 
   }));

  it('has a avatar', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-avatar'][type='file']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.avatar'); 
  }));

  it('has a URL', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-url'][type='text']"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.url'); 
  }));
  
  it('has a user active', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-active'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.user-active'); 
  }));

  it('has a role', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-role-id'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.role'); 
  }));

  it('has a screenshots-active', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-screenshots-active'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.screenshots-active'); 
  }));
  
  it('has a manual-time', async(() => {
    const input = fixture.debugElement.query(By.css("select[name='user-manual-time'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.manual-time');
  }));

  it('has a screenshots interval', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-screenshots-interval'][type='number'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.screenshots-interval');
  }));

  it('has a user inactive time', async(() => {
    const input = fixture.debugElement.query(By.css("input[name='user-computer-time-popup'][type='number'][required]"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.computer-time-popup');
  }));
  
  it('has a timezone', async(() => {
    const input = fixture.debugElement.query(By.css("ng2-timezone-picker"));
    expect(input).not.toBeNull();
    const divRow = input.parent.parent;
    expect(divRow.query(By.css("label")).nativeElement.innerHTML).toContain('field.timezone');
  }));

  it('has a submit', async(() => {
    const input = fixture.debugElement.query(By.css("[type='submit']")).nativeElement;
    expect(input).not.toBeNull();
  }));
});