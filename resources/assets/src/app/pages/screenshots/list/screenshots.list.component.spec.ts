import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ScreenshotsListComponent} from './screenshots.list.component';
import {NgxPaginationModule} from 'ngx-pagination';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {RulesService} from '../../roles/rules.service';
import {RolesService} from '../../roles/roles.service';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {APP_BASE_HREF, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {loadAdminStorage, loadUserStorage} from '../../../test-helper/test-helper';
import {Location} from '@angular/common';
import {ScreenshotsService} from '../screenshots.service';
import {Observable} from '../../../../../../../node_modules/rxjs';
import {By} from '@angular/platform-browser';
import {UsersFiltersComponent} from '../../../filters/users/users.filters.component';
import {ProjectsFiltersComponent} from '../../../filters/projects/projects.filters.component';
import {AttachedUsersService} from '../../users/attached-users.service';
import {AttachedProjectService} from '../../projects/attached-project.service';
import {ProjectsService} from '../../projects/projects.service';
import {UsersService} from '../../users/users.service';
import {ScreenshotListComponent} from '../../../screenshot-list/screenshot-list.component';
import {ModalModule} from 'ngx-bootstrap';

class MockScreenshotListComponent extends ScreenshotListComponent {
  setItems(a) {
    super.setItems([{
      'id': 2218,
      'time_interval_id': 2218,
      'path': 'http://127.0.0.1:8000/uploads/none.png',
      'created_at': '2018-09-03 02:26:09',
      'updated_at': '2018-09-03 02:26:09',
      'deleted_at': null,
      'time_interval': {
        'id': 2218,
        'task_id': 74,
        'start_at': '2006-06-16 14:18:55',
        'end_at': '2006-06-16 14:23:53',
        'created_at': '2018-09-03 02:26:09',
        'updated_at': '2018-09-03 02:26:09',
        'deleted_at': null,
        'count_mouse': 128,
        'count_keyboard': 81,
        'user_id': 1,
        'task': {
          'id': 74,
          'project_id': 5,
          'task_name': 'TaskName',
          'description': 'Ea cupiditate eaque quasi ea sunt error ut. Labore iste deleniti et ducimus odit. Dolores inventore id placeat incidunt. Possimus quia corrupti delectus sed amet placeat quis. Et dolorum amet molestias. Eos aut ut atque. Quam veritatis molestiae voluptatibus corporis sunt corrupti fugit. Inventore possimus voluptatem nihil est natus. Voluptatibus sapiente ex vel et eos commodi aliquid est. Facilis molestiae atque recusandae eveniet. Repellat officiis quo voluptatem corrupti dignissimos. Fugiat enim accusamus labore rerum vel in mollitia. Odio et cum nulla ut. Quisquam nihil fuga blanditiis omnis unde modi adipisci minus. Deserunt magni autem a nostrum adipisci cumque.',
          'active': 1,
          'user_id': 1,
          'assigned_by': 1,
          'url': null,
          'created_at': '2018-09-03 02:25:54',
          'updated_at': '2018-09-03 02:25:54',
          'deleted_at': null,
          'project': {
            'id': 5,
            'company_id': 4,
            'name': 'ProjectName',
            'description': 'Voluptatem consequatur fugit pariatur porro voluptatem eum. Nihil non tenetur distinctio. Dolorem et sed nihil harum. Odit sit ex est sapiente molestiae quo ut quis. Ab mollitia id dolore aliquid. Sed incidunt reprehenderit distinctio inventore aliquam et. Ab ab possimus porro dolorem id aspernatur sit. Optio consequatur aut odit itaque. Voluptas neque error ut deleniti quos magni libero. Sed eum autem consequatur est est quod. Reiciendis neque sed nobis veritatis ad necessitatibus. Nihil nisi veniam quidem cum. Veritatis doloribus labore quisquam. Adipisci ut omnis ullam eaque rem voluptatum. Accusamus et eos aut debitis possimus cumque blanditiis. Sapiente soluta doloremque occaecati voluptas ut illo et. Alias ex velit cupiditate amet laborum. Illo et rem exercitationem adipisci blanditiis.',
            'deleted_at': null,
            'created_at': '2018-09-03 02:22:26',
            'updated_at': '2018-09-03 02:22:26'
          }
        },
        'user': {
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
      }
    }]);
  }

  // reload() {
  //   this.offset = 0;
  //   this.setItems([]);
  //   this.countFail = 0;
  //   this.isAllLoaded = false;
  //   this.loadNext();
  // }
  //
  // loadNext() {
  //   return;
  // }
}

describe('Screenshots list component(Admin)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [MockScreenshotListComponent, UsersFiltersComponent, ProjectsFiltersComponent, ScreenshotsListComponent],
      schemas: [NO_ERRORS_SCHEMA],
      imports: [
        NgxPaginationModule,
        ModalModule.forRoot(),
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }),
      ],
      providers: [
        UsersService,
        ProjectsService,
        AttachedUsersService,
        AttachedProjectService,
        ScreenshotsService,
        RulesService,
        RolesService,
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({id: 123})
          }
        },
      ]
    })
      .compileComponents().then(() => {
      loadAdminStorage();
      fixture = TestBed.createComponent(ScreenshotsListComponent);
      component = fixture.debugElement.componentInstance;
      component.userId = 1;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has a filter by user', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).toContain('filter.user');
  }));

  it('has a filter by project', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).toContain('filter.project');
  }));

  it('has a filter by max date', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.filter'))[2].nativeElement;
    expect(el.innerHTML).toContain('Filter by max date');
  }));

  it('has a filter by min date', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.filter'))[3].nativeElement;
    expect(el.innerHTML).toContain('Filter by min date');
  }));

  it('has a screenshot image', async(() => {
    const el = fixture.debugElement.query(By.css('img.screenshot')).nativeElement;
    expect(el.innerHTML).not.toBeUndefined();
  }));

  it('has a link to task', async(() => {
    const el = fixture.debugElement.query(By.css('.card-body')).nativeElement;
    expect(el.innerHTML).toContain('TaskName');
  }));

  it('has a link to project', async(() => {
    const el = fixture.debugElement.query(By.css('.card-body')).nativeElement;
    expect(el.innerHTML).toContain('ProjectName');
  }));

  it('has a screenshot image', async(() => {
    const el = fixture.debugElement.query(By.css('img.screenshot')).nativeElement;

    expect(el.innerHTML).not.toBeUndefined();
  }));
});


describe('Screenshots list component(User)', () => {
  let component, fixture;
  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [MockScreenshotListComponent, UsersFiltersComponent, ProjectsFiltersComponent, ScreenshotsListComponent],
      schemas: [NO_ERRORS_SCHEMA],
      imports: [
        NgxPaginationModule,
        ModalModule.forRoot(),
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }),
      ],
      providers: [
        UsersService,
        ProjectsService,
        AttachedUsersService,
        AttachedProjectService,
        ScreenshotsService,
        RulesService,
        RolesService,
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({id: 123})
          }
        },
      ]
    })
      .compileComponents().then(() => {
      loadUserStorage();
      fixture = TestBed.createComponent(ScreenshotsListComponent);
      component = fixture.debugElement.componentInstance;
      component.userId = 1;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has not filter by user', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).not.toContain('filter.user');
  }));

  it('has not filter by project', async(() => {
    const el = fixture.debugElement.query(By.css('.panel')).nativeElement;
    expect(el.innerHTML).not.toContain('filter.project');
  }));

  it('has a filter by max date', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.filter'))[0].nativeElement;
    expect(el.innerHTML).toContain('Filter by max date');
  }));

  it('has a filter by min date', async(() => {
    const el = fixture.debugElement.queryAll(By.css('.filter'))[1].nativeElement;
    expect(el.innerHTML).toContain('Filter by min date');
  }));

  it('has a screenshot image', async(() => {
    const el = fixture.debugElement.query(By.css('img.screenshot')).nativeElement;
    expect(el.innerHTML).not.toBeUndefined();
  }));

  it('has a link to task', async(() => {
    const el = fixture.debugElement.query(By.css('.card-body')).nativeElement;
    expect(el.innerHTML).toContain('TaskName');
  }));

  it('has a link to project', async(() => {
    const el = fixture.debugElement.query(By.css('.card-body')).nativeElement;
    expect(el.innerHTML).toContain('ProjectName');
  }));
});
