import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {RolesListComponent} from './roles.list.component';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
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

describe('Roles list component(Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [RolesListComponent, UsersFiltersComponent],
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
      ]
    })
      .compileComponents().then(() => {
      loadAdminStorage();
      fixture = TestBed.createComponent(RolesListComponent);
      component = fixture.debugElement.componentInstance;
      component.userId = 1;
      component.setItems([
        {'id': 1, 'name': 'root', 'deleted_at': null, 'created_at': '2018-09-03 02:10:50', 'updated_at': '2018-09-03 02:10:50'},
        {'id': 2, 'name': 'user', 'deleted_at': null, 'created_at': '2018-09-03 02:10:50', 'updated_at': '2018-09-03 02:10:50'},
        {'id': 3, 'name': 'observer', 'deleted_at': null, 'created_at': '2018-09-03 02:10:50', 'updated_at': '2018-09-03 02:10:50'},
        {'id': 4, 'name': 'client', 'deleted_at': null, 'created_at': '2018-09-03 02:10:50', 'updated_at': '2018-09-03 02:10:50'},
        {'id': 5, 'name': 'manager', 'deleted_at': null, 'created_at': '2018-09-03 02:10:50', 'updated_at': '2018-09-03 02:10:50'},
        {'id': 255, 'name': 'blocked', 'deleted_at': null, 'created_at': '2018-09-03 02:10:50', 'updated_at': '2018-09-03 02:10:50'}
      ]);
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('contains button View for role', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.view');
  }));

  it('contains button Assign for role', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.assign');
  }));

  it('contains button Edit for role', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.edit');
  }));

  it('contains button Delete for role', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.delete');
  }));

  it('contains button "Add new"', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.new');
  }));

  it('not contains button Edit for "root" role', async(() => {
    const el = fixture.debugElement.queryAll(By.css('tbody>tr'))[0].nativeElement;
    expect(el.innerHTML).not.toContain('control.edit');
  }));
});
