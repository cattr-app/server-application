import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ApiService } from '../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router } from '@angular/router';
import { APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy } from '@angular/common';
import { By } from '@angular/platform-browser';
import { DateRangeSelectorComponent } from './date-range-selector.component';
import { AppRoutingModule } from '../app-routing.module';
import { ProjectsService } from '../pages/projects/projects.service';
import { AllowedActionsService } from '../pages/roles/allowed-actions.service';
import { TranslateService, TranslateModule, TranslateLoader, TranslateFakeLoader } from '@ngx-translate/core';
import {DpDatePickerModule} from 'ng2-date-picker';
import * as moment from 'moment';

describe('Date-range-selector component', () => {
    let component, fixture;

    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [DateRangeSelectorComponent],
            schemas: [NO_ERRORS_SCHEMA],
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), DpDatePickerModule
            ],
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
                TranslateService,
            ]
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(DateRangeSelectorComponent);
                component = fixture.debugElement.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', async(() => {
        expect(component).toBeTruthy();
    }));

    it('has a prev button', async(() => {
        expect(fixture.debugElement.query(By.css("button[aria-label='prev']"))).not.toBeNull();
    }));

    it('has a date input', async(() => {
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        console.log(fixture.debugElement.query(By.all()).nativeElement);
        expect(input).not.toBeNull();
    }));

    it('has a next button', async(() => {
        expect(fixture.debugElement.query(By.css("button[aria-label='next']"))).not.toBeNull();
    }));

    it('has a day button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.day")).length).toBe(1);
    }));

    it('has a week button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.week")).length).toBe(1);
    }));

    it('has a month button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.month")).length).toBe(1);
    }));

    it('has a day-range button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).length).toBe(1);
    }));

    it('date range calendar is popup', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();        
        fixture.detectChanges();
        console.log(fixture.debugElement.query(By.css("*")).nativeElement);
        expect(fixture.debugElement.query(By.css("div[data-hidden='false'].popup"))).not.toBeNull();
    }));

    it('upper limit in the calendar is today', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-current-day")).nativeElement.dataset.date)
        .toBe(moment().format('YYYY-MM-DD'));
    }));

    it('upper limit in the calendar is today', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-current-day")).nativeElement.dataset.date)
        .toBe(moment().format('YYYY-MM-DD'));
    }));

    it('calendar date buttons is clickable', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        let tomorrow = moment().add(1, 'day');
        let buttonTomorrowDate = fixture.debugElement.query(By.css(`button[data-date='${tomorrow.format('YYYY-MM-DD')}']`));
        clickEvent = buttonTomorrowDate.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        console.log(fixture.debugElement.query(By.css("button.dp-selected")).nativeElement);
        expect(fixture.debugElement.query(By.css("button.dp-selected")).nativeElement.dataset.date)
        .toBe(tomorrow.format('YYYY-MM-DD'));
    }));

    it('far dates is not clickable', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        let afterTomorrow = moment().add(2, 'day');
        let buttonTomorrowDate = fixture.debugElement
        .query(By.css(`button[data-date='${afterTomorrow.format('YYYY-MM-DD')}']`));
        clickEvent = buttonTomorrowDate.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-selected[disabled]")).nativeElement.dataset.date)
        .toBe(afterTomorrow.format('YYYY-MM-DD'));
    }));
});