import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ProjectsListComponent} from './projects.list.component';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ProjectsService} from '../projects.service';
import {By} from '@angular/platform-browser';
import {loadAdminStorage, loadUserStorage} from '../../../test-helper/test-helper';
import {BsModalService, ComponentLoaderFactory, PositioningService} from 'ngx-bootstrap';
import {AllowedAction} from '../../../models/allowed-action.model';

class ProjectsListComponentMock extends ProjectsListComponent {

  reload() {
    this.setItems([{
      'id': 1,
      'company_id': 0,
      'name': 'Similique harum voluptas ut corporis.',
      'description': 'description',
      'deleted_at': null,
      'created_at': '2018-09-03 02:10:51',
      'updated_at': '2018-09-03 02:10:51'
    }, {
      'id': 2,
      'company_id': 1,
      'name': 'Omnis nihil rerum vel eum quam.',
      'description': 'description',
      'deleted_at': null,
      'created_at': '2018-09-03 02:10:57',
      'updated_at': '2018-09-03 02:10:57'
    }]);
  }
}

describe('Projects list component(Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ProjectsListComponentMock, ],
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
        BsModalService,
        ComponentLoaderFactory,
        PositioningService,
      ]
    })
      .compileComponents().then(() => {
      loadAdminStorage();
      fixture = TestBed.createComponent(ProjectsListComponentMock);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('contains button View', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('View');
  }));

  it('contains button Assign', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('Assign');
  }));

  it('contains button Edit', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('Edit');
  }));

  it('contains button Delete', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('Delete');
  }));

  it('contains button for Add New project ', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('control.new');
  }));
  // FIXME: Не отрисовывается выпадающий список фильтрации по пользователям
  it('contains dropdown for Users', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    // expect(el.innerHTML).toContain('control.users');
  }));
});

describe('Projects list component(User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ProjectsListComponentMock, ],
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
        BsModalService,
        ComponentLoaderFactory,
        PositioningService,
      ]
    })
      .compileComponents().then(() => {
      loadUserStorage();
      fixture = TestBed.createComponent(ProjectsListComponentMock);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('contains button View', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).toContain('View');
  }));

  it('not contains button Assign', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('Assign');
  }));

  it('not contains button Edit', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('Edit');
  }));

  it('not contains button Delete', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('Delete');
  }));

  it('not contains button for Add New project ', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('Add New');
  }));

  it('not contains dropdown for Users', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    expect(el.innerHTML).not.toContain('Select Users');
  }));
});
