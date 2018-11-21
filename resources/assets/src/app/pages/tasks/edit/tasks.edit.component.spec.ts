import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { TasksEditComponent } from './tasks.edit.component';
import { ApiService } from '../../../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { AppRoutingModule } from '../../../app-routing.module';
import { APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy } from '@angular/common';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { TasksService } from '../tasks.service';
import { loadAdminStorage, loadManagerStorage, loadUserStorage } from '../../../test-helper/test-helper';
import { BsModalService, ComponentLoaderFactory, PositioningService } from 'ngx-bootstrap';
import { TranslateFakeLoader, TranslateLoader, TranslateModule } from '@ngx-translate/core';
import { ProjectsService } from '../../projects/projects.service';
import { UsersService } from '../../users/users.service';
import { Observable } from 'rxjs/Observable';
import { By } from '@angular/platform-browser';
import { User } from '../../../models/user.model';


class TasksEditMockComponent extends TasksEditComponent {
  reloadUsersList(empty: boolean = false) {
    if (empty) {
      this.users = [];
    } else {
      this.users = [new User(
        {
          id: 1,
          full_name: 'Fullname 1',
          first_name: 'full 1',
          last_name: 'name 1',
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
        first_name: 'full 2',
        last_name: 'name 2',
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

describe('Tasks edit component (Admin))', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksEditMockComponent,],
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
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({ id: 123 })
          }
        },
      ]
    })
      .compileComponents().then(() => {
        loadAdminStorage();
        fixture = TestBed.createComponent(TasksEditMockComponent);
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
    expect(el.nativeElement.innerHTML).toContain("control.update");
  }));
});

describe('Tasks edit component (Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksEditMockComponent,],
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
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({ id: 123 })
          }
        },
      ]
    })
      .compileComponents().then(() => {
        loadManagerStorage();
        fixture = TestBed.createComponent(TasksEditMockComponent);
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
    expect(el.nativeElement.innerHTML).toContain("control.update");
  }));
});

describe('Tasks edit component (User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksEditMockComponent,],
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
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({ id: 123 })
          }
        },
      ]
    })
      .compileComponents().then(() => {
        loadUserStorage();
        fixture = TestBed.createComponent(TasksEditMockComponent);
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
    expect(el.nativeElement.innerHTML).toContain("control.update");
  }));
});
