/*
    TODO:
    1) Check work with date filters ???
    2) Check work with user filter ???
*/
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { TimeUseReportComponent } from './time-use-report.component';
import {NO_ERRORS_SCHEMA, DebugElement} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {AppRoutingModule} from '../../../app-routing.module';
import {Location, LocationStrategy, PathLocationStrategy, APP_BASE_HREF, CommonModule} from '@angular/common';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {By} from '@angular/platform-browser';
import {LocalStorage} from '../../../api/storage.model';
import {loadAdminStorage, loadUserStorage, loadManagerStorage} from '../../../test-helper/test-helper';
import {TranslateFakeLoader, TranslateLoader, TranslateModule} from '@ngx-translate/core';
import { TabsModule } from 'ngx-bootstrap/tabs';
import {TimeUseReportService} from './time-use-report.service';
import { FormsModule } from '@angular/forms';
import { SharedModule } from '../../../shared.module';
import { LoadingModule } from 'ngx-loading';
import { DateRangeSelectorComponent } from '../../../date-range-selector/date-range-selector.component';
import { UserSelectorComponent } from '../../../user-selector/user-selector.component';
import { UsersService } from '../../users/users.service';
import { NgSelectModule, NgSelectComponent } from '@ng-select/ng-select';
import { DpDatePickerModule } from 'ng2-date-picker';

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
                project_name: "A Amazing Time",
                task_id: 147,
                total_time: 1500
            },
        
            {
                name: "Update screenshots view",
                project_id: 6,
                project_name: "B Amazing Time",
                task_id: 152,
                total_time: 3332
            },
        
            {
                name: "Update project page",
                project_id: 6,
                project_name: "b Amazing Time",
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
  const compareTimeAsStr = function(t1, t2) {
    let [h1, m1, s1] = t1.split(':');
    const d1 = new Date().setHours(h1, m1, s1);
    let [h2, m2, s2] = t2.split(':');    
    const d2 = new Date().setHours(h2, m2, s2);
    let _return = 0;
    if (d1 > d2)
      _return = 1;
    else if(d1 < d2)
      _return = -1;
    
    return _return;
  }
  
  beforeEach(async(() => {
    loadAdminStorage();
    TestBed.configureTestingModule({
      imports: [
        TranslateModule.forRoot({
          loader: {provide: TranslateLoader, useClass: TranslateFakeLoader}
        }), TabsModule.forRoot(), FormsModule, DpDatePickerModule, NgSelectModule,
      ],
      declarations: [TimeUseReportMockComponent, DateRangeSelectorComponent, UserSelectorComponent],
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
        SharedModule,
        CommonModule,
        LoadingModule,
        UsersService,
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

  it('tasks should be order by time ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(compareTimeAsStr);
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTime = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.total-time")
      );
    expect(elementOrderedByTime.length).toBeGreaterThan(0);
    elementOrderedByTime = elementOrderedByTime.shift();
    let clickEventForElementOrderedByTime = elementOrderedByTime.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTime.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTime = clickEventForElementOrderedByTime.shift();
    clickEventForElementOrderedByTime.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by time DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(compareTimeAsStr);
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTime = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.total-time")
      );
    expect(elementOrderedByTime.length).toBeGreaterThan(0);
    elementOrderedByTime = elementOrderedByTime.shift();
    let clickEventForElementOrderedByTime = elementOrderedByTime.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTime.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTime = clickEventForElementOrderedByTime.shift();
    clickEventForElementOrderedByTime.callback.call();
    clickEventForElementOrderedByTime.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by task-name ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.task")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by task-name DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.task")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by project-name ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.project")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by project-name DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.project")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  
  xit('should be apply date-range filter', () => {
    component.reloadReport();
    fixture.detectChanges();
    console.log(fixture.debugElement.nativeElement);
    const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
    let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).shift();
    let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
    clickEvent.callback.call();    
    fixture.detectChanges();
    const input = fixture.debugElement.query(By.css("input[type='text']"));
    clickEvent = input.listeners.filter(event => event.name == 'click').shift();
    clickEvent.callback.call();
    fixture.detectChanges();
  });
});

describe('Time Use Report component (Manager)', () => {
  let fixture, component;
  const compareTimeAsStr = function(t1, t2) {
    let [h1, m1, s1] = t1.split(':');
    const d1 = new Date().setHours(h1, m1, s1);
    let [h2, m2, s2] = t2.split(':');    
    const d2 = new Date().setHours(h2, m2, s2);
    let _return = 0;
    if (d1 > d2)
      _return = 1;
    else if(d1 < d2)
      _return = -1;
    
    return _return;
  }

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

  it('tasks should be order by time ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(compareTimeAsStr);
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTime = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.total-time")
      );
    expect(elementOrderedByTime.length).toBeGreaterThan(0);
    elementOrderedByTime = elementOrderedByTime.shift();
    let clickEventForElementOrderedByTime = elementOrderedByTime.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTime.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTime = clickEventForElementOrderedByTime.shift();
    clickEventForElementOrderedByTime.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by time DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(compareTimeAsStr);
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTime = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.total-time")
      );
    expect(elementOrderedByTime.length).toBeGreaterThan(0);
    elementOrderedByTime = elementOrderedByTime.shift();
    let clickEventForElementOrderedByTime = elementOrderedByTime.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTime.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTime = clickEventForElementOrderedByTime.shift();
    clickEventForElementOrderedByTime.callback.call();
    clickEventForElementOrderedByTime.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by task-name ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.task")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by task-name DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.task")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by project-name ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.project")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by project-name DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.project")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

});

describe('Time Use Report component (User)', () => {
  let fixture, component;
  const compareTimeAsStr = function(t1, t2) {
    let [h1, m1, s1] = t1.split(':');
    const d1 = new Date().setHours(h1, m1, s1);
    let [h2, m2, s2] = t2.split(':');    
    const d2 = new Date().setHours(h2, m2, s2);
    let _return = 0;
    if (d1 > d2)
      _return = 1;
    else if(d1 < d2)
      _return = -1;
    
    return _return;
  }

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


  it('tasks should be order by time ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(compareTimeAsStr);
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTime = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.total-time")
      );
    expect(elementOrderedByTime.length).toBeGreaterThan(0);
    elementOrderedByTime = elementOrderedByTime.shift();
    let clickEventForElementOrderedByTime = elementOrderedByTime.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTime.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTime = clickEventForElementOrderedByTime.shift();
    clickEventForElementOrderedByTime.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by time DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(compareTimeAsStr);
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTime = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.total-time")
      );
    expect(elementOrderedByTime.length).toBeGreaterThan(0);
    elementOrderedByTime = elementOrderedByTime.shift();
    let clickEventForElementOrderedByTime = elementOrderedByTime.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTime.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTime = clickEventForElementOrderedByTime.shift();
    clickEventForElementOrderedByTime.callback.call();
    clickEventForElementOrderedByTime.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-time"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by task-name ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.task")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by task-name DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.task")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by project-name ASC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.project")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim());
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

  it('tasks should be order by project-name DESC', () => {
    component.reloadReport();
    fixture.detectChanges();
    const infoAboutFirstUser = fixture.debugElement.query(By.css("div.report__user"));
    expect(infoAboutFirstUser).not.toBeNull();
    const shouldBe = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .sort(function(s1, s2) {
      return s1.localeCompare(s2);
    });
    expect(shouldBe).not.toBeNull();
    const orderedElements = infoAboutFirstUser.queryAll(By.css("th > span.clickable"));
    expect(orderedElements.length).toBeGreaterThan(0);
    let elementOrderedByTaskName = orderedElements.filter(
      element => element.nativeElement.innerHTML.includes("time-use-report.project")
      );
    expect(elementOrderedByTaskName.length).toBeGreaterThan(0);
    elementOrderedByTaskName = elementOrderedByTaskName.shift();
    let clickEventForElementOrderedByTaskName = elementOrderedByTaskName.listeners.filter(event => event.name == "click");
    expect(clickEventForElementOrderedByTaskName.length).toBeGreaterThan(0);
    clickEventForElementOrderedByTaskName = clickEventForElementOrderedByTaskName.shift();
    clickEventForElementOrderedByTaskName.callback.call();
    clickEventForElementOrderedByTaskName.callback.call();
    fixture.detectChanges();
    const asIs = infoAboutFirstUser.queryAll(By.css("tr.report__task > td.report__task-project-name > a"))
    .map(td => td.nativeElement.innerHTML.trim())
    .reverse();
    expect(asIs).not.toBeNull();
    expect(shouldBe).toEqual(asIs);
  });

});
