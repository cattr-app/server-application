import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { TasksCreateComponent } from './tasks.create.component';
import { ApiService } from '../../../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router } from '@angular/router';
import { AppRoutingModule } from '../../../app-routing.module';
import { APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy } from '@angular/common';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { TasksService } from '../tasks.service';
import { By } from '@angular/platform-browser';
import { loadAdminStorage, loadManagerStorage, loadUserStorage } from '../../../test-helper/test-helper';
import { BsModalService, ComponentLoaderFactory, PositioningService } from 'ngx-bootstrap';
import { TranslateFakeLoader, TranslateLoader, TranslateModule } from '@ngx-translate/core';
import { ProjectsService } from '../../projects/projects.service';
import { Task } from '../../../models/task.model';
import { UsersService } from '../../users/users.service';
import { User, UserData } from '../../../models/user.model';


class TasksCreateMockComponent extends TasksCreateComponent {
  reloadUsersList(empty: boolean = false) {
    if (empty) {
      this.users = [];
    } else {
      this.users = [new User(
        {
          id: 1,
          full_name: 'Fullname 1',
          email: 'email@example1.com',
          url: '',
          company_id: 2,
          level: 'string',
          payroll_access: 0,
          billing_access: 0,
          avatar: 'string',
          screenshots_active: 0,
          manual_time: 0,
          permanent_tasks: 0,
          computer_time_popup: 0,
          poor_time_popup: '0',
          blur_screenshots: 0,
          web_and_app_monitoring: 0,
          webcam_shots: 0,
          screenshots_interval: 0,
          user_role_value: 'string',
          active: 0,
          password: 'string',
          timezone: 'string',
          role_id: 2,
        }),

      new User({
        id: 2,
        full_name: 'Fullname 2',
        email: 'email@example2.com',
        url: '',
        company_id: 2,
        level: 'string',
        payroll_access: 0,
        billing_access: 0,
        avatar: 'string',
        screenshots_active: 0,
        manual_time: 0,
        permanent_tasks: 0,
        computer_time_popup: 0,
        poor_time_popup: '0',
        blur_screenshots: 0,
        web_and_app_monitoring: 0,
        webcam_shots: 0,
        screenshots_interval: 0,
        user_role_value: 'string',
        active: 0,
        password: 'string',
        timezone: 'string',
        role_id: 2,
      })
      ];
    }
  }
}

describe('Tasks create component (Admin))', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksCreateMockComponent,],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        { provide: Router, useClass: AppRoutingModule },
        Location,
        { provide: LocationStrategy, useClass: PathLocationStrategy },
        { provide: APP_BASE_HREF, useValue: '/' },
        AllowedActionsService,
        ProjectsService,
        TasksService,
        BsModalService,
        ComponentLoaderFactory,
        PositioningService,
        UsersService,
      ]
    })
      .compileComponents().then(() => {
        loadAdminStorage();
        fixture = TestBed.createComponent(TasksCreateMockComponent);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has back button', async(() => {
    let backButton = fixture.debugElement.query(By.css("a[title='Back']"));
    expect(backButton).not.toBeNull();
    expect(backButton.nativeElement.innerHTML).toContain("control.back");
  }));

  it('has field task name', async(() => {
    let el = fixture.debugElement.query(By.css("input[type='text'][name='task-name']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.name");
  }));

  it('has selector project', async(() => {
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select project']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.project");
  }));

  it('has field task description', async(() => {
    let el = fixture.debugElement.query(By.css("textarea[name='task-description']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.description");
  }));

  it('has selector priority', async(() => {
    let el = fixture.debugElement.query(By.css("select[name='priority']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.priority");
  }));

  it('has selector \'user\' if has users', async(() => {
    component.reloadUsersList();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.user");
  }));

  it('has not selector \'user\' if has not users', async(() => {
    component.reloadUsersList(true);
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).toBeNull();
  }));

  it('has submit button', async(() => {
    let el = fixture.debugElement.query(By.css("button[type='submit']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.create");
  }));
});

describe('Tasks create component (Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksCreateMockComponent,],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        { provide: Router, useClass: AppRoutingModule },
        Location,
        { provide: LocationStrategy, useClass: PathLocationStrategy },
        { provide: APP_BASE_HREF, useValue: '/' },
        AllowedActionsService,
        ProjectsService,
        TasksService,
        BsModalService,
        ComponentLoaderFactory,
        PositioningService,
        UsersService,
      ]
    })
      .compileComponents().then(() => {
        loadManagerStorage();
        fixture = TestBed.createComponent(TasksCreateMockComponent);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has back button', async(() => {
    let backButton = fixture.debugElement.query(By.css("a[title='Back']"));
    expect(backButton).not.toBeNull();
    expect(backButton.nativeElement.innerHTML).toContain("control.back");
  }));

  it('has field task name', async(() => {
    let el = fixture.debugElement.query(By.css("input[type='text'][name='task-name']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.name");
  }));

  it('has selector project', async(() => {
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select project']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.project");
  }));

  it('has field task description', async(() => {
    let el = fixture.debugElement.query(By.css("textarea[name='task-description']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.description");
  }));

  it('has selector priority', async(() => {
    let el = fixture.debugElement.query(By.css("select[name='priority']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.priority");
  }));

  it('has selector \'user\' if has users', async(() => {
    component.reloadUsersList();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.user");
  }));

  it('has not selector \'user\' if has not users', async(() => {
    component.reloadUsersList(true);
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).toBeNull();
  }));

  it('has submit button', async(() => {
    let el = fixture.debugElement.query(By.css("button[type='submit']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.create");
  }));
});

describe('Tasks create component (User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksCreateMockComponent,],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        { provide: Router, useClass: AppRoutingModule },
        Location,
        { provide: LocationStrategy, useClass: PathLocationStrategy },
        { provide: APP_BASE_HREF, useValue: '/' },
        AllowedActionsService,
        ProjectsService,
        TasksService,
        BsModalService,
        ComponentLoaderFactory,
        PositioningService,
        UsersService,
      ]
    })
      .compileComponents().then(() => {
        loadUserStorage();
        fixture = TestBed.createComponent(TasksCreateMockComponent);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has back button', async(() => {
    let backButton = fixture.debugElement.query(By.css("a[title='Back']"));
    expect(backButton).not.toBeNull();
    expect(backButton.nativeElement.innerHTML).toContain("control.back");
  }));

  it('has field task name', async(() => {
    let el = fixture.debugElement.query(By.css("input[type='text'][name='task-name']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.name");
  }));

  it('has selector project', async(() => {
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select project']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.project");
  }));

  it('has field task description', async(() => {
    let el = fixture.debugElement.query(By.css("textarea[name='task-description']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.description");
  }));

  it('has selector priority', async(() => {
    let el = fixture.debugElement.query(By.css("select[name='priority']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.priority");
  }));

  it('has selector \'user\' if has users', async(() => {
    component.reloadUsersList();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).not.toBeNull();
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.user");
  }));

  it('has not selector \'user\' if has not users', async(() => {
    component.reloadUsersList(true);
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).toBeNull();
  }));

  it('has submit button', async(() => {
    let el = fixture.debugElement.query(By.css("button[type='submit']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.create");
  }));
});
