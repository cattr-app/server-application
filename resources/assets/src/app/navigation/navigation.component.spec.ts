import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import {ApiService} from '../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {By} from '@angular/platform-browser';
import {LocalStorage} from '../api/storage.model';
import {NavigationComponent} from './navigation.component';
import {AppRoutingModule} from '../app-routing.module';
import {ProjectsService} from '../pages/projects/projects.service';
import {AllowedActionsService} from '../pages/roles/allowed-actions.service';

describe('Navigation component(Manager)', () => {
  let component, fixture;
  const localStorage = LocalStorage.getStorage();
  let nav;

  beforeEach(async(() => {
    localStorage.set('allowed_actions', [
      {
        'object': 'attached-users',
        'action': 'bulk-create',
        'name': 'Attached User relation multiple create'
      },
      {
        'object': 'attached-users',
        'action': 'bulk-remove',
        'name': 'Attached User relation multiple remove'
      },
      {
        'object': 'attached-users',
        'action': 'create',
        'name': 'Attached User relation create'
      },
      {
        'object': 'attached-users',
        'action': 'full_access',
        'name': 'Attached User relation full access'
      },
      {
        'object': 'attached-users',
        'action': 'list',
        'name': 'Attached User relation list'
      },
      {
        'object': 'attached-users',
        'action': 'remove',
        'name': 'Attached User relation remove'
      },
      {
        'object': 'dashboard',
        'action': 'manager_access',
        'name': 'Dashboard manager access'
      },
      {
        'object': 'project-report',
        'action': 'list',
        'name': 'Projects report list'
      },
      {
        'object': 'project-report',
        'action': 'manager_access',
        'name': 'Projects report manager access'
      },
      {
        'object': 'project-report',
        'action': 'projects',
        'name': 'Projects report related projects'
      },
      {
        'object': 'projects',
        'action': 'create',
        'name': 'Project create'
      },
      {
        'object': 'projects',
        'action': 'edit',
        'name': 'Project edit'
      },
      {
        'object': 'projects',
        'action': 'full_access',
        'name': 'Project full access'
      },
      {
        'object': 'projects',
        'action': 'list',
        'name': 'Project list'
      },
      {
        'object': 'projects',
        'action': 'relations',
        'name': 'Project list attached to user'
      },
      {
        'object': 'projects',
        'action': 'remove',
        'name': 'Project remove'
      },
      {
        'object': 'projects',
        'action': 'show',
        'name': 'Project show'
      },
      {
        'object': 'projects-roles',
        'action': 'bulk-create',
        'name': 'Project Role relation multiple create'
      },
      {
        'object': 'projects-roles',
        'action': 'bulk-remove',
        'name': 'Project Role relation multiple remove'
      },
      {
        'object': 'projects-roles',
        'action': 'create',
        'name': 'Project Role relation create'
      },
      {
        'object': 'projects-roles',
        'action': 'full_access',
        'name': 'Project Role relation full access'
      },
      {
        'object': 'projects-roles',
        'action': 'list',
        'name': 'Project Role relation list'
      },
      {
        'object': 'projects-roles',
        'action': 'remove',
        'name': 'Project Role relation remove'
      },
      {
        'object': 'projects-users',
        'action': 'bulk-create',
        'name': 'Project User relation multiple create'
      },
      {
        'object': 'projects-users',
        'action': 'bulk-remove',
        'name': 'Project User relation multiple remove'
      },
      {
        'object': 'projects-users',
        'action': 'create',
        'name': 'Project User relation create'
      },
      {
        'object': 'projects-users',
        'action': 'full_access',
        'name': 'Project User relation full access'
      },
      {
        'object': 'projects-users',
        'action': 'list',
        'name': 'Project User relation list'
      },
      {
        'object': 'projects-users',
        'action': 'remove',
        'name': 'Project User relation remove'
      },
      {
        'object': 'roles',
        'action': 'allowed-rules',
        'name': 'Role allowed rule list'
      },
      {
        'object': 'roles',
        'action': 'create',
        'name': 'Role create'
      },
      {
        'object': 'roles',
        'action': 'edit',
        'name': 'Role edit'
      },
      {
        'object': 'roles',
        'action': 'full_access',
        'name': 'Roles full access'
      },
      {
        'object': 'roles',
        'action': 'list',
        'name': 'Role list'
      },
      {
        'object': 'roles',
        'action': 'remove',
        'name': 'Role remove'
      },
      {
        'object': 'roles',
        'action': 'show',
        'name': 'Role show'
      },
      {
        'object': 'rules',
        'action': 'actions',
        'name': 'Rules actions list'
      },
      {
        'object': 'rules',
        'action': 'bulk-edit',
        'name': 'Rules multiple edit'
      },
      {
        'object': 'rules',
        'action': 'edit',
        'name': 'Rules edit'
      },
      {
        'object': 'screenshots',
        'action': 'create',
        'name': 'Screenshot create'
      },
      {
        'object': 'screenshots',
        'action': 'dashboard',
        'name': 'Screenshot list at dashboard'
      },
      {
        'object': 'screenshots',
        'action': 'edit',
        'name': 'Screenshot edit'
      },
      {
        'object': 'screenshots',
        'action': 'full_access',
        'name': 'Screenshots full access'
      },
      {
        'object': 'screenshots',
        'action': 'list',
        'name': 'Screenshot list'
      },
      {
        'object': 'screenshots',
        'action': 'manager_access',
        'name': 'Screenshots manager access'
      },
      {
        'object': 'screenshots',
        'action': 'remove',
        'name': 'Screenshot remove'
      },
      {
        'object': 'screenshots',
        'action': 'show',
        'name': 'Screenshot show'
      },
      {
        'object': 'tasks',
        'action': 'create',
        'name': 'Task create'
      },
      {
        'object': 'tasks',
        'action': 'dashboard',
        'name': 'Task list at dashboard'
      },
      {
        'object': 'tasks',
        'action': 'edit',
        'name': 'Task edit'
      },
      {
        'object': 'tasks',
        'action': 'full_access',
        'name': 'Tasks full access'
      },
      {
        'object': 'tasks',
        'action': 'list',
        'name': 'Task list'
      },
      {
        'object': 'tasks',
        'action': 'remove',
        'name': 'Task remove'
      },
      {
        'object': 'tasks',
        'action': 'show',
        'name': 'Task show'
      },
      {
        'object': 'time',
        'action': 'full_access',
        'name': 'Time full access'
      },
      {
        'object': 'time',
        'action': 'project',
        'name': 'Time by project'
      },
      {
        'object': 'time',
        'action': 'task',
        'name': 'Time by single task'
      },
      {
        'object': 'time',
        'action': 'task-user',
        'name': 'Time by single task and user'
      },
      {
        'object': 'time',
        'action': 'tasks',
        'name': 'Time by tasks'
      },
      {
        'object': 'time',
        'action': 'total',
        'name': 'Time total'
      },
      {
        'object': 'time-intervals',
        'action': 'create',
        'name': 'Time interval create'
      },
      {
        'object': 'time-intervals',
        'action': 'edit',
        'name': 'Time interval edit'
      },
      {
        'object': 'time-intervals',
        'action': 'full_access',
        'name': 'Time intervals full access'
      },
      {
        'object': 'time-intervals',
        'action': 'list',
        'name': 'Time interval list'
      },
      {
        'object': 'time-intervals',
        'action': 'remove',
        'name': 'Time interval remove'
      },
      {
        'object': 'time-intervals',
        'action': 'show',
        'name': 'Time interval show'
      },
      {
        'object': 'users',
        'action': 'bulk-edit',
        'name': 'User multiple edit'
      },
      {
        'object': 'users',
        'action': 'create',
        'name': 'User create'
      },
      {
        'object': 'users',
        'action': 'edit',
        'name': 'User edit'
      },
      {
        'object': 'users',
        'action': 'full_access',
        'name': 'Users full access'
      },
      {
        'object': 'users',
        'action': 'list',
        'name': 'User list'
      },
      {
        'object': 'users',
        'action': 'relations',
        'name': 'Attached users list'
      },
      {
        'object': 'users',
        'action': 'remove',
        'name': 'User remove'
      },
      {
        'object': 'users',
        'action': 'show',
        'name': 'User show'
      }
    ]);
    localStorage.set('user',
      {
        'id': 1,
        'full_name': 'Admin',
        'first_name': 'Ad',
        'last_name': 'Min',
        'email': 'admin@example.com',
        'url': null,
        'company_id': 1,
        'level': 'admin',
        'payroll_access': 1,
        'billing_access': 1,
        'avatar': null,
        'screenshots_active': 1,
        'manual_time': 0,
        'permanent_tasks': 0,
        'computer_time_popup': 300,
        'poor_time_popup': null,
        'blur_screenshots': 0,
        'web_and_app_monitoring': 1,
        'webcam_shots': 0,
        'screenshots_interval': 9,
        'user_role_value': '1',
        'active': 'active',
        'deleted_at': null,
        'created_at': '2018-09-03 02:10:51',
        'updated_at': '2018-09-03 02:31:23',
        'role_id': 1,
        'timezone': null
      }
    );

    TestBed.configureTestingModule({
      declarations: [ NavigationComponent, ],
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
      fixture = TestBed.createComponent(NavigationComponent);
      component = fixture.debugElement.componentInstance;
      component.setAuth(true);
      fixture.detectChanges();
      nav = fixture.debugElement.query(By.all()).nativeElement;
      console.log(nav);
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has menu item "dashboard"', async(() => {
    expect(nav.innerHTML).toContain('navigation.dashboard');
  }));

  it('has menu item "projects report"', async(() => {
    expect(nav.innerHTML).toContain('navigation.projectsreport');
  }));

  it('has menu item "projects"', async(() => {
    expect(nav.innerHTML).toContain('navigation.projects');
  }));

  it('has menu item "tasks"', async(() => {
    expect(nav.innerHTML).toContain('navigation.tasks');
  }));

  it('has menu item "users"', async(() => {
    expect(nav.innerHTML).toContain('navigation.users');
  }));

  it('has menu item "screenshots"', async(() => {
    expect(nav.innerHTML).toContain('navigation.screenshots');
  }));

  it('has menu item "integrations"', async(() => {
    expect(nav.innerHTML).toContain('navigation.integrations');
  }));

  it('has menu item "role"', async(() => {
    expect(nav.innerHTML).toContain('navigation.role');
  }));

  it('has menu item "settings"', async(() => {
    expect(nav.innerHTML).toContain('navigation.settings');
  }));

  it('has menu item "logout"', async(() => {
    expect(nav.innerHTML).toContain('navigation.logout');
  }));
});
