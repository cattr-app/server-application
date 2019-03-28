import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {RolesCreateComponent} from './roles.create.component';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {ProjectsService} from '../../projects/projects.service';
import {By} from '@angular/platform-browser';
import {loadAdminStorage} from '../../../test-helper/test-helper';
import {Observable} from '../../../../../../../node_modules/rxjs';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {UsersService} from '../../users/users.service';
import {RolesService} from '../roles.service';
import {DualListComponent} from 'angular-dual-listbox';
import {AllowedActionsService} from '../allowed-actions.service';
import {RulesService} from '../rules.service';

describe('Roles create component(Admin)', () => {
  let component, fixture;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [RolesCreateComponent, DualListComponent],
      schemas: [NO_ERRORS_SCHEMA],
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
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
        ProjectsService,
        AllowedActionsService,
        RulesService,
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({id: 123})
          }
        },
        UsersService,
        RolesService,
      ]
    })
      .compileComponents().then(() => {
      loadAdminStorage();
      fixture = TestBed.createComponent(RolesCreateComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('contains "role name" field', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).toContain('role-name');
  }));

  it('contains "Add" button ', async(() => {
    const el = fixture.debugElement.queryAll(By.css('button[name=addBtn]'))[0].nativeElement;
    expect(el.innerHTML).toContain('control.add');
  }));

  it('contains "Remove" button ', async(() => {
    const el = fixture.debugElement.queryAll(By.css('button[name=removeBtn]'))[0].nativeElement;
    expect(el.innerHTML).toContain('control.remove');
  }));

  it('contains "Create" button for role ', async(() => {
    const el = fixture.debugElement.query(By.css('button[type=submit]')).nativeElement;
    expect(el.innerHTML).toContain('control.create');
  }));
});
