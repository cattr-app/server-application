
/*
TODO: 

TESTS

1) Changing month/week/day via prev/next buttons (local storage and value in input).
2) Button "Aplly" closes calendar.
3) Added Checking active status of button after clicking on it. 

*/

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
import { DpDatePickerModule } from 'ng2-date-picker';
import * as moment from 'moment';
import { LocalStorage } from '../api/storage.model';

describe('Date-range-selector component', () => {
    let component, fixture;

    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [DateRangeSelectorComponent],
            schemas: [NO_ERRORS_SCHEMA],
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), DpDatePickerModule,
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
        expect(fixture.debugElement.query(By.css("div[data-hidden='false'].popup"))).not.toBeNull();
    }));

    it('current day in calendar (date-range) is today', async(() => {
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

    it('calendar date buttons is clickable (date-range)', async(() => {
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
        expect(fixture.debugElement.query(By.css("button.dp-selected")).nativeElement.dataset.date)
            .toBe(tomorrow.format('YYYY-MM-DD'));
    }));

    it('far dates is not clickable (date range)', async(() => {
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

    it('month calendar is popup', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.month")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("div[data-hidden='false'].dp-popup"))).not.toBeNull();
    }));

    it('current month in calendar (month) is today', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.month")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-current-month")).nativeElement.dataset.date)
            .toBe(moment().startOf('month').format("YYYY-MM-DD"));
    }));

    it('calendar date buttons is clickable (month)', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.month")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        let lastMonth = moment().startOf('month').subtract(1, 'month');
        let buttonLastMonth = fixture.debugElement.query(By.css(`button[data-date='${lastMonth.format('YYYY-MM-DD')}']`));
        clickEvent = buttonLastMonth.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-selected")).nativeElement.dataset.date)
            .toBe(lastMonth.format('YYYY-MM-DD'));
    }));

    it('far dates is not clickable (month)', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.month")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        let nextMonth = moment().add(1, 'month');
        let buttonNextMonth = fixture.debugElement.query(
            By.css(`button[data-date='${nextMonth.format('YYYY-MM-DD')}']`)
        );
        clickEvent = buttonNextMonth.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-selected[disabled]")).nativeElement.dataset.date)
            .toBe(nextMonth.format('YYYY-MM-DD'));
    }));

    it('week calendar is popup', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.week")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("div[data-hidden='false'].dp-popup"))).not.toBeNull();
    }));

    it('current day in calendar (week) is today', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.week")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-current-day")).nativeElement.dataset.date)
            .toBe(moment().format('YYYY-MM-DD'));
    }));

    it('calendar date buttons is clickable (week)', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.week")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        let nextWeek = moment().subtract(1, 'week').day("Monday");
        let buttonNextWeekStartDay = fixture.debugElement.query(
            By.css(`button[data-date='${nextWeek.format('YYYY-MM-DD')}']`)
        );
        clickEvent = buttonNextWeekStartDay.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-selected")).nativeElement.dataset.date)
            .toBe(nextWeek.format('YYYY-MM-DD'));
    }));

    it('far dates is not clickable (week)', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.week")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        let afterTomorrow = moment().add(1, 'week').day("Monday");
        let buttonTomorrowDate = fixture.debugElement
            .query(By.css(`button[data-date='${afterTomorrow.format('YYYY-MM-DD')}']`));
        clickEvent = buttonTomorrowDate.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-selected[disabled]")).nativeElement.dataset.date)
            .toBe(afterTomorrow.format('YYYY-MM-DD'));
    }));

    it('day calendar is popup', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.day")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("div[data-hidden='false'].dp-popup"))).not.toBeNull();
    }));

    it('current day in calendar (day) is today', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.day")).shift();
        let clickEvent = btn.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        clickEvent = input.listeners.filter(event => event.name == 'click').shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button.dp-current-day")).nativeElement.dataset.date)
            .toBe(moment().format('YYYY-MM-DD'));
    }));

    it('calendar date buttons is clickable (day)', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.day")).shift();
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
        expect(fixture.debugElement.query(By.css("button.dp-selected")).nativeElement.dataset.date)
            .toBe(tomorrow.format('YYYY-MM-DD'));
    }));

    it('far dates is not clickable (day)', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btn = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.day")).shift();
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

    it('has not prev buttons if select date-range', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        const btnDateRange = buttonsDate.filter(
            button => button.nativeElement.innerHTML.includes("control.date-range")
        ).shift();
        const clickEventBtnDateRange = btnDateRange.listeners.filter(event => event.name == 'click').shift();
        clickEventBtnDateRange.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button[aria-label='prev']"))).toBeNull();
    }));

    it('has not next buttons if select date-range', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        let btnDateRange = buttonsDate.filter(
            button => button.nativeElement.innerHTML.includes("control.date-range")
        ).shift();
        const clickEventBtnDateRange = btnDateRange.listeners.filter(event => event.name == 'click').shift();
        clickEventBtnDateRange.callback.call();
        fixture.detectChanges();
        expect(fixture.debugElement.query(By.css("button[aria-label='next']"))).toBeNull();
    }));

    xit('change month via prev button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        const btnMonth = buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.month")).shift();
        const clickEventBtnMonth = btnMonth.listeners.filter(event => event.name == 'click').shift();
        clickEventBtnMonth.callback.call();
        fixture.detectChanges();
        console.log(LocalStorage.getStorage().get("filterByDateRangeStartIN/context.html"));
        const prevButton = fixture.debugElement.query(By.css("button[aria-label='prev']"));
        const clickEventBtnPrev = prevButton.listeners.filter(event => event.name == 'click').shift();
        clickEventBtnPrev.callback.call();
        fixture.detectChanges();
        const input = fixture.debugElement.query(By.css("input[type='text']"));
        const clickEventDateInput = input.listeners.filter(event => event.name == 'click').shift();
        clickEventDateInput.callback.call();
        fixture.detectChanges();
        console.log(fixture.debugElement.query(By.css(".dp-selected")));
        console.log(LocalStorage.getStorage().get("filterByDateRangeStartIN/context.html"));
    }));
});