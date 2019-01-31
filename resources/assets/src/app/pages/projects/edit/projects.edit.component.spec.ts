import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ProjectsEditComponent} from './projects.edit.component';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ProjectsService} from '../projects.service';
import {By} from '@angular/platform-browser';
import {LocalStorage} from '../../../api/storage.model';
import {loadAdminStorage} from '../../../test-helper/test-helper';
import {Observable} from '../../../../../../../node_modules/rxjs';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {UsersService} from '../../users/users.service';
import {RolesService} from '../../roles/roles.service';
import {DualListComponent} from 'angular-dual-listbox';

describe('Projects edit component(Manager)', () => {
  let component, fixture;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ProjectsEditComponent, DualListComponent],
      schemas: [NO_ERRORS_SCHEMA],
      imports: [
        TranslateModule.forRoot({
          loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
        }),
      ],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        ProjectsService,
        {
          provide: ActivatedRoute,
          useValue: {
            params: Observable.of({id: 123})
          }
        },
        UsersService,
        RolesService,
      ]
    })
      .compileComponents().then(() => {
      loadAdminStorage();
      fixture = TestBed.createComponent(ProjectsEditComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('users add button is exist', async(() => {
    const addBtn = fixture.debugElement.queryAll(By.css('button[name=addBtn]'))[0].nativeElement;
    expect(addBtn.innerHTML).toContain('control.add');
  }));

  it('roles add button is exist', async(() => {
    const addBtn = fixture.debugElement.queryAll(By.css('button[name=addBtn]'))[1].nativeElement;
    expect(addBtn.innerHTML).toContain('control.add');
  }));

  it('users remove button is exist', async(() => {
    const addBtn = fixture.debugElement.queryAll(By.css('button[name=removeBtn]'))[0].nativeElement;
    expect(addBtn.innerHTML).toContain('control.remove');
  }));

  it('roles remove button is exist', async(() => {
    const addBtn = fixture.debugElement.queryAll(By.css('button[name=removeBtn]'))[1].nativeElement;
    expect(addBtn.innerHTML).toContain('control.remove');
  }));

  it('update button is exist', async(() => {
    const addBtn = fixture.debugElement.queryAll(By.css('button[type=submit]'))[0].nativeElement;
    expect(addBtn.innerHTML).toContain('control.update');
  }));

  it('back button is exist', async(() => {
    const addBtn = fixture.debugElement.queryAll(By.css('button.btn-warning'))[0].nativeElement;
    expect(addBtn.innerHTML).toContain('control.back');
  }));

  it('contains "name" field', async(() => {
    const el = fixture.debugElement.query(By.css('form')).nativeElement;
    console.log(el.innerHTML);
    expect(el.innerHTML).toContain('field.name');
  }));

  it('contains "description" field', async(() => {
    const el = fixture.debugElement.query(By.css('form')).nativeElement;
    expect(el.innerHTML).toContain('field.description');
  }));
});
