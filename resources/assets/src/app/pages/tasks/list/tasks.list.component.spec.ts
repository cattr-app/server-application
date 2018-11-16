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

class TaskListComponentMock extends TasksListComponent {

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
    expect(fixture.debugElement.query(By.css("a[title='Add new task']"))).not.toBeNull();
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
});
