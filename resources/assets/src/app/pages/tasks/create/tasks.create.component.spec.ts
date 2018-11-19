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

describe('Tasks create component(Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      declarations: [TasksCreateComponent,],
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
        fixture = TestBed.createComponent(TasksCreateComponent);
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

  xit('has selector user', async(() => {
    console.log(fixture.debugElement.nativeElement);
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).not.toBeNull();    
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.user");
  }));

  it('has submit button', async(() => {
    let el = fixture.debugElement.query(By.css("button[type='submit']"));
    expect(el).not.toBeNull();
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
      declarations: [TasksCreateComponent,],
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
        fixture = TestBed.createComponent(TasksCreateComponent);
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

  xit('has selector user', async(() => {
    console.log(fixture.debugElement.nativeElement);
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).not.toBeNull();    
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.user");
  }));

  it('has submit button', async(() => {
    let el = fixture.debugElement.query(By.css("button[type='submit']"));
    expect(el).not.toBeNull();
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
      declarations: [TasksCreateComponent,],
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
        fixture = TestBed.createComponent(TasksCreateComponent);
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

  xit('has selector user', async(() => {
    console.log(fixture.debugElement.nativeElement);
    let el = fixture.debugElement.query(By.css("ng-select[placeholder='Select user']"));
    expect(el).not.toBeNull();    
    expect(el.parent.parent.nativeElement.innerHTML).toContain("field.user");
  }));

  it('has submit button', async(() => {
    let el = fixture.debugElement.query(By.css("button[type='submit']"));
    expect(el).not.toBeNull();
  }));
});
