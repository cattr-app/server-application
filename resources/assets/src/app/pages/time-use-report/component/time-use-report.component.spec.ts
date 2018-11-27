/*
    TODO:
    1) Check links (task & project)
    2) Check work with date filters
    3) Check work with user filter
    4) Check ordering (tasks / projects / time)
*/
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { TimeUseReportComponent } from './time-use-report.component';
import {NO_ERRORS_SCHEMA, DebugElement} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {Location, LocationStrategy, PathLocationStrategy, APP_BASE_HREF} from '@angular/common';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {By} from '@angular/platform-browser';
import {LocalStorage} from '../../../api/storage.model';
import {loadAdminStorage, loadUserStorage, loadManagerStorage} from '../../../test-helper/test-helper';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import { TabsModule } from 'ngx-bootstrap/tabs';
import {TimeUseReportService} from './time-use-report.service';

class TimeUseReportMockComponent extends TimeUseReportComponent {

    getReport() {
        return {
            users: [
                {
                    name: "Alexander Yanchuk",
                    tasks: this.getTasks(),
                    total_time: 5910,
                    user_id: 19
                },

                {
                    name: "Vyacheslav Sokolov",
                    tasks: this.getTasks(),
                    total_time: 5910,
                    user_id: 22,
                    avatar: "https://www.google.ru/url?sa=i&source=images&cd=&cad=rja&uact=8&ved=2ahUKEwjQl4er-fPeAhVRkMMKHcT4BLQQjRx6BAgBEAU&url=https%3A%2F%2Fwww.w3schools.com%2Fhowto%2Fhowto_css_image_avatar.asp&psig=AOvVaw1898aHirsA3bito85Z1SRr&ust=1543386611681901"
                }
            ]
        };
    }

    getTasks() {
        return [
            {
                name: "task view page",
                project_id: 6,
                project_name: "Amazing Time",
                task_id: 147,
                total_time: 1500
            },
        
            {
                name: "Update screenshots view",
                project_id: 6,
                project_name: "Amazing Time",
                task_id: 152,
                total_time: 3332
            },
        
            {
                name: "Update project page",
                project_id: 6,
                project_name: "Amazing Time",
                task_id: 153,
                total_time: 1078
            }
        ];
    }

    reloadReport(empty: boolean = false) {
        if (empty) {
            this.report = { users: [] };
        } else {
            this.report = this.getReport();
        }
    }
}

describe('Time Use Report component (Admin)', () => {
  let fixture, component;
  
  beforeEach(async(() => {
    loadAdminStorage();
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot(),
      ],
      declarations: [TimeUseReportMockComponent],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        TimeUseReportService,
      ],
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(TimeUseReportMockComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('report info should be not empty if report not empty', () => {
      component.reloadReport();
      fixture.detectChanges();
      const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
      expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    });

  it('report info should be empty if report empty', () => {
    component.reloadReport(true);
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBe(0);
  });

  it('first user shoud be have not avatar', () => {
      component.reloadReport();
      fixture.detectChanges();
      const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
      expect(elementsReportUserInfo.length).toBeGreaterThan(0)
      const firstUserReportInfo = elementsReportUserInfo[0];
      const avatar = firstUserReportInfo.query(By.css("img"));
      expect(avatar).toBeNull();
    });

  it('second user shoud be have avatar', () => {
    component.reloadReport();
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    const secondUserReportInfo = elementsReportUserInfo[1];
    const avatar = secondUserReportInfo.query(By.css("img"));
    expect(avatar).not.toBeNull();
  });

  it('total time-use for first user should be 1h 38m', () => {
    component.reloadReport();
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    const firstUserReportInfo = elementsReportUserInfo[0];
    const containerForTimeUseOfFirstUser = firstUserReportInfo.query(By.css("span.report__user-time"));
    expect(containerForTimeUseOfFirstUser.nativeElement.innerHTML).toContain("1h 38m"); 
  });
});

describe('Time Use Report component (Manager)', () => {
  let fixture, component;
  
  beforeEach(async(() => {
    loadManagerStorage();
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot(),
      ],
      declarations: [TimeUseReportMockComponent],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        TimeUseReportService,
      ],
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(TimeUseReportMockComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('report info should be not empty if report not empty', () => {
      component.reloadReport();
      fixture.detectChanges();
      const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
      expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    });

  it('report info should be empty if report empty', () => {
    component.reloadReport(true);
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBe(0);
  });

  it('first user shoud be have not avatar', () => {
      component.reloadReport();
      fixture.detectChanges();
      const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
      expect(elementsReportUserInfo.length).toBeGreaterThan(0)
      const firstUserReportInfo = elementsReportUserInfo[0];
      const avatar = firstUserReportInfo.query(By.css("img"));
      expect(avatar).toBeNull();
    });

  it('second user shoud be have avatar', () => {
    component.reloadReport();
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    const secondUserReportInfo = elementsReportUserInfo[1];
    const avatar = secondUserReportInfo.query(By.css("img"));
    expect(avatar).not.toBeNull();
  });

  it('total time-use for first user should be 1h 38m', () => {
    component.reloadReport();
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    const firstUserReportInfo = elementsReportUserInfo[0];
    const containerForTimeUseOfFirstUser = firstUserReportInfo.query(By.css("span.report__user-time"));
    expect(containerForTimeUseOfFirstUser.nativeElement.innerHTML).toContain("1h 38m"); 
  });
});

describe('Time Use Report component (User)', () => {
  let fixture, component;
  beforeEach(async(() => {
    loadUserStorage();

    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot(),
      ],
      declarations: [TimeUseReportMockComponent],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        TimeUseReportService,
      ],
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(TimeUseReportMockComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('report info should be not empty if report not empty', () => {
      component.reloadReport();
      fixture.detectChanges();
      const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
      expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    });

  it('report info should be empty if report empty', () => {
    component.reloadReport(true);
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBe(0);
  });

  it('first user shoud be have not avatar', () => {
      component.reloadReport();
      fixture.detectChanges();
      const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
      expect(elementsReportUserInfo.length).toBeGreaterThan(0)
      const firstUserReportInfo = elementsReportUserInfo[0];
      const avatar = firstUserReportInfo.query(By.css("img"));
      expect(avatar).toBeNull();
    });

  it('second user shoud be have avatar', () => {
    component.reloadReport();
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    const secondUserReportInfo = elementsReportUserInfo[1];
    const avatar = secondUserReportInfo.query(By.css("img"));
    expect(avatar).not.toBeNull();
  });

  it('total time-use for first user should be 1h 38m', () => {
    component.reloadReport();
    fixture.detectChanges();
    const elementsReportUserInfo = fixture.debugElement.queryAll(By.css("div.report__user"));
    expect(elementsReportUserInfo.length).toBeGreaterThan(0);
    const firstUserReportInfo = elementsReportUserInfo[0];
    const containerForTimeUseOfFirstUser = firstUserReportInfo.query(By.css("span.report__user-time"));
    expect(containerForTimeUseOfFirstUser.nativeElement.innerHTML).toContain("1h 38m"); 
  });
});
