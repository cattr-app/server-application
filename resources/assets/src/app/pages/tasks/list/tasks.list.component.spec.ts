import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { TasksListComponent } from './tasks.list.component';
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

class TaskListComponentMock extends TasksListComponent {
  
  tasks: Task[] = [
    {
    'id': 1,
    'project_id': 1,
    'task_name': 'Task name #1',
    'description': 'description #1',
    'deleted_at': null,
    'created_at': '2018-09-03 02:10:51',
    'updated_at': '2018-09-03 02:10:51'
  },

  {
    'id': 2,
    'project_id': 1,
    'task_name': 'Task name #2',
    'description': 'description #2',
    'deleted_at': null,
    'created_at': '2018-09-03 02:10:31',
    'updated_at': '2018-09-03 02:10:31'
  },

  {
    'id': 3,
    'project_id': 1,
    'task_name': 'Task name #3',
    'description': 'description #3',
    'deleted_at': null,
    'created_at': '2018-09-03 02:10:41',
    'updated_at': '2018-09-03 02:10:41'
  }
]

  reload() {
    this.setItems(this.tasks);
    this.setDirectProjects();
  }

  setDirectProjects() {
    let set = new Set();
    this.tasks.forEach(task => {
      set.add(task.project_id);
    });
    this.directProject = Array.from(set);
  }
}

describe('Tasks list component(Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TaskListComponentMock,],
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
      ]
    })
      .compileComponents().then(() => {
        loadAdminStorage();
        fixture = TestBed.createComponent(TaskListComponentMock);
        component = fixture.debugElement.componentInstance;
        component.reload();
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has filter by users', async(() => {
    expect(fixture.debugElement.query(By.css("app-users-filters"))).not.toBeNull();
  }));

  it('has filter by projects', async(() => {
    expect(fixture.debugElement.query(By.css("app-projects-filters"))).not.toBeNull();
  }));

  it('has button add new task', async(() => {
    expect(fixture.debugElement.query(By.css("a[title='Add new task'"))).not.toBeNull();
  }));

  it('has button view task', async(() => {
    let infoButtons = fixture.debugElement.queryAll(By.css("button.btn-info"));
    expect(infoButtons).not.toBeNull();
    let index = Math.floor(Math.random() * component.tasks.length);
    expect(infoButtons[index].nativeElement.innerHTML).toContain("control.view");
  }));

  it('has button edit task', async(() => {
    let editButtons = fixture.debugElement.queryAll(By.css("button.btn-primary"));
    expect(editButtons).not.toBeNull();
    let index = Math.floor(Math.random() * component.tasks.length);
    expect(editButtons[index].nativeElement.innerHTML).toContain("control.edit");
  }));

  it('has button delete task', async(() => {
    let delButtons = fixture.debugElement.queryAll(By.css("button.btn-danger"));
    expect(delButtons).not.toBeNull();
    let index = Math.floor(Math.random() * component.tasks.length);
    expect(delButtons[index].nativeElement.innerHTML).toContain("control.delete");
  }));
});

describe('Tasks list component(Manager)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TaskListComponentMock,],
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
      ]
    })
      .compileComponents().then(() => {
        loadManagerStorage();
        fixture = TestBed.createComponent(TaskListComponentMock);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has filter by users', async(() => {
    expect(fixture.debugElement.query(By.css("app-users-filters"))).not.toBeNull();
  }));

  it('has filter by projects', async(() => {
    expect(fixture.debugElement.query(By.css("app-projects-filters"))).not.toBeNull();
  }));

  it('has not button add new task', async(() => {
    expect(fixture.debugElement.query(By.css("a[title='Add new task']"))).toBeNull();
  }));

  it('has button view task', async(() => {
    let infoButtons = fixture.debugElement.queryAll(By.css("button.btn-info"));
    expect(infoButtons).not.toBeNull();
    let index = Math.floor(Math.random() * component.tasks.length);
    expect(infoButtons[index].nativeElement.innerHTML).toContain("control.view");
  }));

  it('has button edit task', async(() => {
    let editButtons = fixture.debugElement.queryAll(By.css("button.btn-primary"));
    expect(editButtons).not.toBe([]);
  }));

  it('has button delete task', async(() => {
    let delButtons = fixture.debugElement.queryAll(By.css("button.btn-danger"));
    expect(delButtons).not.toBe([]);
  }));
});

describe('Tasks list component(User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TaskListComponentMock,],
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
      ]
    })
      .compileComponents().then(() => {
        loadUserStorage();
        fixture = TestBed.createComponent(TaskListComponentMock);
        component = fixture.debugElement.componentInstance;
        fixture.detectChanges();
      });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has not filter by users', async(() => {
    expect(fixture.debugElement.query(By.css("app-users-filters"))).toBeNull();
  }));

  it('has not filter by projects', async(() => {
    expect(fixture.debugElement.query(By.css("app-projects-filters"))).toBeNull();
  }));

  it('has button add new task', async(() => {
    expect(fixture.debugElement.query(By.css("a[title='Add new task']"))).not.toBeNull();
  }));

  it('has button view task', async(() => {
    let infoButtons = fixture.debugElement.queryAll(By.css("button.btn-info"));
    expect(infoButtons).not.toBeNull();
    let index = Math.floor(Math.random() * component.tasks.length);
    expect(infoButtons[index].nativeElement.innerHTML).toContain("control.view");
  }));

  it('has button edit task', async(() => {
    let editButtons = fixture.debugElement.queryAll(By.css("button.btn-primary"));
    expect(editButtons).not.toBeNull();
    let index = Math.floor(Math.random() * component.tasks.length);
    expect(editButtons[index].nativeElement.innerHTML).toContain("control.edit");
  }));

  it('has button delete task', async(() => {
    let delButtons = fixture.debugElement.queryAll(By.css("button.btn-danger"));
    expect(delButtons).not.toBeNull();
    let index = Math.floor(Math.random() * component.tasks.length);
    expect(delButtons[index].nativeElement.innerHTML).toContain("control.delete");
  }));
});
