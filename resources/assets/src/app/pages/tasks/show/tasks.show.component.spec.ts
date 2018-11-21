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
      this.item = null;
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
        created_at: '',
        updated_at: '',
        total_time: '',
        user: new User(),
        assigned: new User(),
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
    expect(tableRows).not.toBe([]);
    let tableRowId = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.id"));
    expect(tableRowId).not.toBe([]);
    tableRowId = tableRowId.shift();
    expect(tableRowId.nativeElement.innerHTML).toContain(component.item.id);
  }));

  it('has field project', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows).not.toBe([]);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject).not.toBe([]);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/projects/show/${component.item.project.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.project.name);
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
    expect(tableRows).not.toBe([]);
    let tableRowId = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.id"));
    expect(tableRowId).not.toBe([]);
    tableRowId = tableRowId.shift();
    expect(tableRowId.nativeElement.innerHTML).toContain(component.item.id);
  }));

  it('has field project', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows).not.toBe([]);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject).not.toBe([]);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/projects/show/${component.item.project.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.project.name);
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
    expect(tableRows).not.toBe([]);
    let tableRowId = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.id"));
    expect(tableRowId).not.toBe([]);
    tableRowId = tableRowId.shift();
    expect(tableRowId.nativeElement.innerHTML).toContain(component.item.id);
  }));

  it('has field project', async(() => {
    component.reloadTask();
    fixture.detectChanges();
    let tableRows = fixture.debugElement.queryAll(By.css("tr"));
    expect(tableRows).not.toBe([]);
    let tableRowProject = tableRows.filter(row => row.nativeElement.innerHTML.includes("field.project"));
    expect(tableRowProject).not.toBe([]);
    tableRowProject = tableRowProject.shift();
    let projectLink = tableRowProject.query(By.css("a"));
    expect(projectLink).not.toBeNull();
    expect(projectLink.properties.href).toEqual(`/projects/show/${component.item.project.id}`);
    expect(projectLink.nativeElement.innerHTML).toContain(component.item.project.name);
  }));
});
