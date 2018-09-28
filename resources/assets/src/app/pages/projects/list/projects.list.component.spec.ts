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
import {loadAdminStorage} from '../../../test-helper/test-helper';
import {BsModalService, ComponentLoaderFactory, PositioningService} from 'ngx-bootstrap';

describe('Projects list component(Manager)', () => {
  let component, fixture;
  loadAdminStorage();
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ProjectsListComponent, ],
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
      fixture = TestBed.createComponent(ProjectsListComponent);
      component = fixture.debugElement.componentInstance;
      // const itemsService = TestBed.get(item);
      spyOn(component.itemService, 'getItems').and.returnValue([{
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
      component.reload();
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('contains button View', async(() => {
    const el = fixture.debugElement.query(By.all()).nativeElement;
    console.log(el);
    expect(el.innerHTML).toContain('View');
  }));
});
