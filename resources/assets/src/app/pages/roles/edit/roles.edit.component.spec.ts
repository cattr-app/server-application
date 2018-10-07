import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {RolesEditComponent} from './roles.Edit.component';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {AllowedActionsService} from '../allowed-actions.service';
import {ProjectsService} from '../../projects/projects.service';
import {By} from '@angular/platform-browser';
import {loadAdminStorage, loadUserStorage} from '../../../test-helper/test-helper';
import {BsModalService, ComponentLoaderFactory, PositioningService} from 'ngx-bootstrap';
import {AllowedAction} from '../../../models/allowed-action.model';
import {UsersFiltersComponent} from '../../../filters/users/users.filters.component';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {AttachedUsersService} from '../../users/attached-users.service';
import {ItemsListComponent} from '../../items.list.component';
import {NgxPaginationModule} from 'ngx-pagination';
import {RolesService} from '../roles.service';
import {RulesService} from '../rules.service';
import {Observable} from '../../../../../../../node_modules/rxjs';
import {UsersService} from '../../users/users.service';
import {DualListComponent} from 'angular-dual-listbox';

describe('zRoles edit component(Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [RolesEditComponent, DualListComponent],
      schemas: [NO_ERRORS_SCHEMA],
      imports: [
        NgxPaginationModule,
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }),
      ],
      providers: [
        RulesService,
        RolesService,
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        ProjectsService,
        BsModalService,
        ComponentLoaderFactory,
        PositioningService,
        AttachedUsersService,
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({id: 123})
          }
        },
        UsersService,
      ]
    })
      .compileComponents().then(() => {
      loadAdminStorage();
      fixture = TestBed.createComponent(RolesEditComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('contains button Update', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).toContain('control.update');
  }));

  it('contains button Back', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).toContain('control.back');
  }));

  it('contains field RoleName for role', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).toContain('role.rolename');
  }));

  it('contains button Add for rules', async(() => {
    const el = fixture.debugElement.queryAll(By.css('button[name="addBtn"]'))[0].nativeElement;
    expect(el.innerHTML).toContain('control.add');
  }));

  it('contains button Add for users', async(() => {
    const el = fixture.debugElement.queryAll(By.css('button[name="addBtn"]'))[1].nativeElement;
    expect(el.innerHTML).toContain('control.add');
  }));

  it('contains button Remove for rules', async(() => {
    const el = fixture.debugElement.queryAll(By.css('button[name="removeBtn"]'))[0].nativeElement;
    expect(el.innerHTML).toContain('control.remove');
  }));

  it('contains button Remove for users', async(() => {
    const el = fixture.debugElement.queryAll(By.css('button[name="removeBtn"]'))[1].nativeElement;
    expect(el.innerHTML).toContain('control.remove');
  }));

  it('contains button All for rules', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.button-bar'))[0].nativeElement;
    expect(el.innerHTML).toContain('control.all');
  }));

  it('contains button All for users', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.button-bar'))[1].nativeElement;
    expect(el.innerHTML).toContain('control.all');
  }));

  it('contains button None for rules', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.button-bar'))[0].nativeElement;
    expect(el.innerHTML).toContain('control.none');
  }));

  it('contains button None for users', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.button-bar'))[1].nativeElement;
    expect(el.innerHTML).toContain('control.none');
  }));

});
