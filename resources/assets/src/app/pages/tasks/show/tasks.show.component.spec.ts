import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA, DebugElement } from '@angular/core';
import { TasksShowComponent } from './tasks.show.component';
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
import { Task } from '../../../models/task.model';
import { User } from '../../../models/user.model';
import { Project } from '../../../models/project.model';


class TasksShowMockComponent extends TasksShowComponent {
  reloadTask(empty: boolean = false) {
    if (empty) {
      this.item = new Task();
    }
    else {
      this.item = this.getTaskMock();
    }
  }

  getTaskMock() {
    return new Task(
      {
        id: 1,
        project_id: 1,
        task_name: 'task name',
        description: 'description',
        active: 1,
        user_id: 21,
        assigned_by: 21,
        url: 'https://redmine.amazingcat.net/issues/6212',
        deleted_at: '',
        created_at:"2018-08-07 00:00:00",
        updated_at:"2018-08-08 00:00:00",
        total_time: '',
        user: this.getUserMock(),
        assigned: this.getUserMock(),
        project: this.getProjectMock(),
        priority: { id: 1, name: 'Low' },
        priority_id: 1
      }
    );
  }

  getProjectMock() {
    return new Project(
      {
        id: 100,
        name: "Project name",
        description: "Project description",
        deleted_at: '',
        created_at: '',
        updated_at: '',
        users: [new User()]
      }
    );
  }

  getUserMock() {
    return new User({
      id: 21,
      full_name: "User full name",
      email: "example@example.com",
      url: null,
      company_id: 2,
      level: null,
      payroll_access: null,
      billing_access: null,
      avatar: null,
      screenshots_active: 1,
      manual_time: 300,
      permanent_tasks: null,
      computer_time_popup: 300,
      poor_time_popup: "300",
      blur_screenshots: 300,
      web_and_app_monitoring: 300,
      webcam_shots: 300,
      screenshots_interval: 300,
      user_role_value: "some role",
      active: 1,
      password: "string",
      timezone: "string",
      role_id: 2
   })
  }

  reloadUsers(empty: boolean = false) {
    if (empty) {
      this.users = [];
    }
    else {
      this.users = this.getUsersMock();
    }
  }

  getUsersMock() {
    return [
      {
        user: this.getUserMock(),
        time: 1000 * 3600, // 1 h
      },
      {
        user: this.getUserMock(),
        time: 2200,
      },
    ];
  }

  reloadTotalTime(empty: boolean = false) {
    if (empty) {
      this.totalTime = 0;
    }
    else {
      this.totalTime = 1000 * 3600 * 1.5;
    }
  }
}

describe('Tasks show component (Admin))', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksShowMockComponent,],
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
        fixture = TestBed.createComponent(TasksShowMockComponent);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has back button', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='Back']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.back");
  }));

  it('has edit button', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='Edit task']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.edit");
  }));

  it('has view-in-redmine button if set redmine url', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML.toLowerCase()).toContain("redmine");
  }));

  it('has not view-in-redmine button if unset redmine url', async(() => {
    fixture.detectChanges();
    component.reloadTask(true);
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).toBeNull();
  }));

  it('has view-in-redmine button if unset redmine url', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).toBeNull();
  }));

  it('has field task-name', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css(".task-name"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain(component.item.task_name);
  }));

  it('has field id', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowId = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.id"));
    expect(tableRowId.length).toBeGreaterThan(0);
    tableRowId = tableRowId.shift();
    expect(tableRowId.nativeElement.innerHTML).toContain(component.item.id);
  }));

  it('has field project (if setted project)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/projects/show/${component.item.project.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.project.name);
  }));

  it('has not field project (if unsetted project)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field active (active)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.active"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("Yes");
    expect(tableRowActive.nativeElement.innerHTML).not.toContain("No");
  }));

  it('has field active (no active)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.active"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("No");
    expect(tableRowActive.nativeElement.innerHTML).not.toContain("Yes");
  }));

  it('has field user (if setted user)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.user"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/users/show/${component.item.user.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.user.full_name);
  }));

  it('has not field user (if unsetted user)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.user"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field assigned (if setted assigned)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.assigned"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/users/show/${component.item.assigned.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.assigned.full_name);
  }));

  it('has not field assigned (if unsetted assigned)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.assigned"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field created', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowCreated = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.created"));
    expect(tableRowCreated.length).toBeGreaterThan(0);
    tableRowCreated = tableRowCreated.shift();
    expect(tableRowCreated.nativeElement.innerHTML).toContain(component.item.created_at);
  }));

  it('has field updated', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowUpdated = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.updated"));
    expect(tableRowUpdated.length).toBeGreaterThan(0);
    tableRowUpdated = tableRowUpdated.shift();
    expect(tableRowUpdated.nativeElement.innerHTML).toContain(component.item.updated_at);
  }));

  it('has field total time', async(() => {
    component.reloadTask();
    component.reloadTotalTime();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowUpdated = tableRows.filter(row => row.nativeElement.innerHTML.includes("Total time"));
    expect(tableRowUpdated.length).toBeGreaterThan(0);
    tableRowUpdated = tableRowUpdated.shift();
    expect(tableRowUpdated.nativeElement.innerHTML).toContain("1h 30m");
  }));

  it('has field priority (if setted priority)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowPriority = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.priority"));
    expect(tableRowPriority.length).toBeGreaterThan(0);
    tableRowPriority = tableRowPriority.shift();
    expect(tableRowPriority.nativeElement.innerHTML).toContain(component.item.priority.name);
  }));

  it('has not field priority (if unsetted priority)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowPriority = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.priority"));
    expect(tableRowPriority.length).toBeGreaterThan(0);
  }));

  it('has field description', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.description"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain(component.item.description);
  }));

  it('has users info (if setted users)', async(() => {
    component.reloadTask();
    component.reloadUsers();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("accordion-group"));
    expect(tableRows.length).toBeGreaterThan(0);
    let InfoAboutFirstUser = component.users[0];
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes(InfoAboutFirstUser.user.full_name));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("1h");
  }));

  it('has users info (if unsetted users)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("accordion-group"));
    expect(tableRows.length).toBe(0);
  }));
});

describe('Tasks show component (Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksShowMockComponent,],
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
        fixture = TestBed.createComponent(TasksShowMockComponent);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has back button', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='Back']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.back");
  }));

  it('has edit button', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='Edit task']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.edit");
  }));

  it('has view-in-redmine button if set redmine url', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML.toLowerCase()).toContain("redmine");
  }));

  it('has not view-in-redmine button if unset redmine url', async(() => {
    fixture.detectChanges();
    component.reloadTask(true);
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).toBeNull();
  }));

  it('has view-in-redmine button if unset redmine url', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).toBeNull();
  }));

  it('has field task-name', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css(".task-name"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain(component.item.task_name);
  }));

  it('has field id', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowId = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.id"));
    expect(tableRowId.length).toBeGreaterThan(0);
    tableRowId = tableRowId.shift();
    expect(tableRowId.nativeElement.innerHTML).toContain(component.item.id);
  }));

  it('has field project (if setted project)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/projects/show/${component.item.project.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.project.name);
  }));

  it('has not field project (if unsetted project)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field active (active)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.active"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("Yes");
    expect(tableRowActive.nativeElement.innerHTML).not.toContain("No");
  }));

  it('has field active (no active)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.active"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("No");
    expect(tableRowActive.nativeElement.innerHTML).not.toContain("Yes");
  }));

  it('has field user (if setted user)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.user"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/users/show/${component.item.user.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.user.full_name);
  }));

  it('has not field user (if unsetted user)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.user"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field assigned (if setted assigned)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.assigned"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/users/show/${component.item.assigned.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.assigned.full_name);
  }));

  it('has not field assigned (if unsetted assigned)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.assigned"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field created', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowCreated = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.created"));
    expect(tableRowCreated.length).toBeGreaterThan(0);
    tableRowCreated = tableRowCreated.shift();
    expect(tableRowCreated.nativeElement.innerHTML).toContain(component.item.created_at);
  }));

  it('has field updated', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowUpdated = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.updated"));
    expect(tableRowUpdated.length).toBeGreaterThan(0);
    tableRowUpdated = tableRowUpdated.shift();
    expect(tableRowUpdated.nativeElement.innerHTML).toContain(component.item.updated_at);
  }));

  it('has field total time', async(() => {
    component.reloadTask();
    component.reloadTotalTime();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowUpdated = tableRows.filter(row => row.nativeElement.innerHTML.includes("Total time"));
    expect(tableRowUpdated.length).toBeGreaterThan(0);
    tableRowUpdated = tableRowUpdated.shift();
    expect(tableRowUpdated.nativeElement.innerHTML).toContain("1h 30m");
  }));

  it('has field priority (if setted priority)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowPriority = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.priority"));
    expect(tableRowPriority.length).toBeGreaterThan(0);
    tableRowPriority = tableRowPriority.shift();
    expect(tableRowPriority.nativeElement.innerHTML).toContain(component.item.priority.name);
  }));

  it('has not field priority (if unsetted priority)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowPriority = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.priority"));
    expect(tableRowPriority.length).toBeGreaterThan(0);
  }));

  it('has field description', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.description"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain(component.item.description);
  }));

  it('has users info (if setted users)', async(() => {
    component.reloadTask();
    component.reloadUsers();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("accordion-group"));
    expect(tableRows.length).toBeGreaterThan(0);
    let InfoAboutFirstUser = component.users[0];
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes(InfoAboutFirstUser.user.full_name));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("1h");
  }));

  it('has users info (if unsetted users)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("accordion-group"));
    expect(tableRows.length).toBe(0);
  }));
});

describe('Tasks show component (User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksShowMockComponent,],
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
        fixture = TestBed.createComponent(TasksShowMockComponent);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has back button', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='Back']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.back");
  }));

  it('has edit button', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='Edit task']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain("control.edit");
  }));

  it('has view-in-redmine button if set redmine url', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML.toLowerCase()).toContain("redmine");
  }));

  it('has not view-in-redmine button if unset redmine url', async(() => {
    fixture.detectChanges();
    component.reloadTask(true);
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).toBeNull();
  }));

  it('has view-in-redmine button if unset redmine url', async(() => {
    let el = fixture.debugElement.query(By.css("a[title='View in redmine']"));
    expect(el).toBeNull();
  }));

  it('has field task-name', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let el = fixture.debugElement.query(By.css(".task-name"));
    expect(el).not.toBeNull();
    expect(el.nativeElement.innerHTML).toContain(component.item.task_name);
  }));

  it('has field id', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowId = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.id"));
    expect(tableRowId.length).toBeGreaterThan(0);
    tableRowId = tableRowId.shift();
    expect(tableRowId.nativeElement.innerHTML).toContain(component.item.id);
  }));

  it('has field project (if setted project)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/projects/show/${component.item.project.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.project.name);
  }));

  it('has not field project (if unsetted project)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field active (active)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.active"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("Yes");
    expect(tableRowActive.nativeElement.innerHTML).not.toContain("No");
  }));

  it('has field active (no active)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.active"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("No");
    expect(tableRowActive.nativeElement.innerHTML).not.toContain("Yes");
  }));

  it('has field user (if setted user)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.user"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/users/show/${component.item.user.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.user.full_name);
  }));

  it('has not field user (if unsetted user)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.user"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field assigned (if setted assigned)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.assigned"));
    expect(tableRowProject.length).toBeGreaterThan(0);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/users/show/${component.item.assigned.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.assigned.full_name);
  }));

  it('has not field assigned (if unsetted assigned)', async(() => {
    component.reloadTask(true);
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.assigned"));
    expect(tableRowProject.length).toBe(0);
  }));

  it('has field created', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowCreated = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.created"));
    expect(tableRowCreated.length).toBeGreaterThan(0);
    tableRowCreated = tableRowCreated.shift();
    expect(tableRowCreated.nativeElement.innerHTML).toContain(component.item.created_at);
  }));

  it('has field updated', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowUpdated = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.updated"));
    expect(tableRowUpdated.length).toBeGreaterThan(0);
    tableRowUpdated = tableRowUpdated.shift();
    expect(tableRowUpdated.nativeElement.innerHTML).toContain(component.item.updated_at);
  }));

  it('has field total time', async(() => {
    component.reloadTask();
    component.reloadTotalTime();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowUpdated = tableRows.filter(row => row.nativeElement.innerHTML.includes("Total time"));
    expect(tableRowUpdated.length).toBeGreaterThan(0);
    tableRowUpdated = tableRowUpdated.shift();
    expect(tableRowUpdated.nativeElement.innerHTML).toContain("1h 30m");
  }));

  it('has field priority (if setted priority)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowPriority = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.priority"));
    expect(tableRowPriority.length).toBeGreaterThan(0);
    tableRowPriority = tableRowPriority.shift();
    expect(tableRowPriority.nativeElement.innerHTML).toContain(component.item.priority.name);
  }));

  it('has not field priority (if unsetted priority)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowPriority = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.priority"));
    expect(tableRowPriority.length).toBeGreaterThan(0);
  }));

  it('has field description', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows.length).toBeGreaterThan(0);
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.description"));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain(component.item.description);
  }));

  it('has users info (if setted users)', async(() => {
    component.reloadTask();
    component.reloadUsers();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("accordion-group"));
    expect(tableRows.length).toBeGreaterThan(0);
    let InfoAboutFirstUser = component.users[0];
    let tableRowActive = tableRows.filter(row => row.nativeElement.innerHTML.includes(InfoAboutFirstUser.user.full_name));
    expect(tableRowActive.length).toBeGreaterThan(0);
    tableRowActive = tableRowActive.shift();
    expect(tableRowActive.nativeElement.innerHTML).toContain("1h");
  }));

  it('has users info (if unsetted users)', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("accordion-group"));
    expect(tableRows.length).toBe(0);
  }));
});
