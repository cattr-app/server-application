import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ProjectsCreateComponent} from './projects.create.component';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ProjectsService} from '../projects.service';
import {By} from '@angular/platform-browser';
import {LocalStorage} from '../../../api/storage.model';

describe('Projects create component(Manager)', () => {
  let component, fixture;
  const localStorage = LocalStorage.getStorage();

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ProjectsCreateComponent, ],
      schemas: [ NO_ERRORS_SCHEMA ],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        { provide: LocationStrategy, useClass: PathLocationStrategy },
        { provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        ProjectsService
      ]
    })
      .compileComponents().then(() => {
        fixture = TestBed.createComponent(ProjectsCreateComponent);
        component = fixture.debugElement.componentInstance;
        localStorage.set('allowed_actions', [{
          'object': 'dashboard',
          'action': 'manager_access',
          'name': 'Dashboard manager access'
        }]);
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('contains "name" and "description" fields', async(() => {
    const el  = fixture.debugElement.query(By.css('form')).nativeElement;
    expect(el.innerHTML).toContain('project-name');
    expect(el.innerHTML).toContain('project-description');
  }));
});
