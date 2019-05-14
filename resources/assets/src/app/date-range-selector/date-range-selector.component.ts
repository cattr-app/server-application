import { Component, OnInit, Input, Output, EventEmitter, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { DatePickerComponent } from 'ng2-date-picker';

import * as moment from 'moment';

import { TranslateService } from '@ngx-translate/core';

import { LocalStorage } from '../api/storage.model';
import { ActivatedRoute } from '@angular/router';

export interface Range {
    start: moment.Moment;
    end: moment.Moment;
}

@Component({
    selector: 'app-date-range-selector',
    templateUrl: './date-range-selector.component.html',
    styleUrls: ['./date-range-selector.component.scss']
})
export class DateRangeSelectorComponent implements OnInit, AfterViewInit {
    @ViewChild('dateInput') dateInput: ElementRef;
    @ViewChild('datePicker') datePicker: DatePickerComponent;

    @Input() start: moment.Moment = moment().startOf('day');
    @Input() end: moment.Moment = this.start.clone().add(1, 'day');
    @Input() mode: string = 'day';

    @Output() startChanged = new EventEmitter<moment.Moment>();
    @Output() endChanged = new EventEmitter<moment.Moment>();
    @Output() rangeChanged = new EventEmitter<Range>();
    @Output() modeChanged = new EventEmitter<string>();

    readonly max: moment.Moment = moment().add(1, 'day');

    // May be different to an active mode.
    // Used to make button active as soon as user presses it.
    activeButton: string = 'day';
    // True, when user presses a button.
    isActive: boolean = false;

    isRangePopupOpened: boolean = false;

    get _input(): string {
        switch (this.mode) {
            default:
            case 'day':
                return this.start.format('YYYY-MM-DD');

            case 'week': {
                const start = this.start.format('YYYY-MM-DD');
                const end = this.start.clone().add(6, 'days').format('YYYY-MM-DD');
                return `${start} - ${end}`;
            }

            case 'month':
                return this.start.format('YYYY MMMM');

            case 'range': {
                const start = this.start.format('YYYY-MM-DD');
                const end = this.end.format('YYYY-MM-DD');
                return `${start} - ${end}`;
            }
        }
    }

    set _input(value: string) {
        switch (this.mode) {
            default:
            case 'day':
                if (moment(value, 'YYYY-MM-DD', true).isValid()) {
                    const date = moment(value, 'YYYY-MM-DD').startOf('day');
                    this.setStart(date);
                }
                break;

            case 'week':
                const val = value.split(' - ')[0];
                if (moment(val, 'YYYY-MM-DD', true).isValid()) {
                    const date = moment(val, 'YYYY-MM-DD').startOf('day');
                    this.setStart(date);
                }
                break;

            case 'month':
                if (moment(value, 'YYYY MMMM', true).isValid()) {
                    const date = moment(value, 'YYYY MMMM').startOf('day');
                    this.setStart(date);
                }
                break;

            case 'range':
                const values = value.split(' - ');
                if (values.length > 0 && moment(values[0], 'YYYY-MM-DD', true).isValid()) {
                    const date = moment(values[0], 'YYYY-MM-DD').startOf('day');
                    this.setStart(date);
                }
                if (values.length > 1 && moment(values[1], 'YYYY-MM-DD', true).isValid()) {
                    const date = moment(values[1], 'YYYY-MM-DD').startOf('day');
                    this.setEnd(date);
                }
                break;
        }
    }

    get _datePickerMode() {
        return this.mode === 'month' ? 'month' : 'day';
    }

    constructor(
        public translate: TranslateService,
        private activatedRoute: ActivatedRoute,
    ) {
        const params = this.activatedRoute.snapshot.queryParams;
        if (params.start || params.end || params.range) {
            if (params.range) {
                this.mode = params.range;
                this.activeButton = params.range;
            }

            if (params.start) {
                this.start = moment(params.start, 'YYYY-MM-DD');
            }

            if (params.end) {
                this.end = moment(params.end, 'YYYY-MM-DD');
            }

            this.applyChanges();
        } else {
            const savedMode = LocalStorage.getStorage().get(`filterByDateRangeModeIN${window.location.pathname}`);
            const savedStart = LocalStorage.getStorage().get(`filterByDateRangeStartIN${window.location.pathname}`);
            const savedEnd = LocalStorage.getStorage().get(`filterByDateRangeEndIN${window.location.pathname}`);

            if (savedMode) {
                this.mode = savedMode;
                this.activeButton = savedMode;
            }

            if (savedMode !== 'day') {
                if (savedStart) {
                    this.start = moment(savedStart, 'YYYY-MM-DD');
                }

                if (savedEnd) {
                    this.end = moment(savedEnd, 'YYYY-MM-DD');
                }
            }

            if (savedMode || savedStart || savedEnd) {
                this.applyChanges();
            }
        }
    }

    ngOnInit() {
    }

    ngAfterViewInit() {
        //this.datePicker.appendToElement = this.dateInput.nativeElement;
    }

    setStart(start: moment.Moment) {
        switch (this.mode) {
            default:
            case 'day': {
                if (start.diff(this.max) > 0) {
                    start = this.max.clone();
                }

                this.start = start;
                this.end = start.clone().add(1, 'day');
                break;
            }

            case 'week': {
                if (start.diff(this.max) > 0) {
                    start = this.max.clone();
                }

                this.start = start.startOf('week').add(1, 'day');
                this.end = this.start.clone().add(1, 'week');
                break;
            }

            case 'month': {
                if (start.diff(this.max) > 0) {
                    start = this.max.clone();
                }

                this.start = start.startOf('month');
                this.end = this.start.clone().add(1, 'month');
                break;
            }

            case 'range': {
                if (start.diff(this.max) > 0) {
                    start = this.max.clone();
                }

                this.start = start;

                if (start.diff(this.end) > 0) {
                    this.end = this.start.clone().add(1, 'day');
                }
                break;
            }
        }

        if (this.mode !== 'range' || !this.isRangePopupOpened) {
            this.applyChanges();
        }
    }

    setEnd(end: moment.Moment) {
        switch (this.mode) {
            default:
            case 'day': {
                if (end.diff(this.max) > 0) {
                    end = this.max.clone();
                }

                this.end = end;
                this.start = end.clone().subtract(1, 'day');
                break;
            }

            case 'week': {
                if (end.diff(this.max) > 0) {
                    end = this.max.clone();
                }

                this.end = end.startOf('week').add(1, 'day');
                this.start = this.end.clone().subtract(1, 'week');
                break;
            }

            case 'month': {
                if (end.diff(this.max) > 0) {
                    end = this.max.clone();
                }

                this.end = end.startOf('month');
                this.start = this.end.clone().subtract(1, 'month');
                break;
            }

            case 'range': {
                if (end.diff(this.max) > 0) {
                    end = this.max.clone();
                }

                this.end = end;

                if (this.start.diff(this.end) > 0) {
                    this.start = this.end.clone().subtract(1, 'day');
                }
                break;
            }
        }

        if (this.mode !== 'range' || !this.isRangePopupOpened) {
            this.applyChanges();
        }
    }

    buttonClick(mode: string) {
        const prevMode = this.mode;
        this.setMode(mode);

        if (prevMode !== 'day' && mode === 'day') {
            // Select today when switching to a day view.
            this.setStart(moment().startOf('day'));
        }
    }

    setMode(mode: string) {
        this.mode = mode;
        this.activeButton = mode;

        switch (this.mode) {
            default:
            case 'day':
                this.setStart(this.start.clone().startOf('day'));
                break;

            case 'week':
                this.setStart(this.start.clone().startOf('week').add(1, 'day'));
                break;

            case 'month':
                this.setStart(this.start.clone().startOf('month'));
                break;
        }

        if (this.mode !== 'range' || !this.isRangePopupOpened) {
            this.applyChanges();
        }
    }

    selectPrevious() {
        switch (this.mode) {
            default:
            case 'day':
                this.setStart(this.start.clone().subtract(1, 'day').startOf('day'));
                break;

            case 'week':
                this.setStart(this.start.clone().subtract(1, 'week').startOf('week').add(1, 'day'));
                break;

            case 'month':
                this.setStart(this.start.clone().subtract(1, 'month').startOf('month'));
                break;
        }
    }

    selectNext() {
        switch (this.mode) {
            default:
            case 'day':
                this.setStart(this.start.clone().add(1, 'day').startOf('day'));
                break;

            case 'week':
                this.setStart(this.start.clone().add(1, 'week').startOf('week').add(1, 'day'));
                break;

            case 'month':
                this.setStart(this.start.clone().add(1, 'month').startOf('month'));
                break;
        }
    }

    dateInputClick() {
        if (this.mode !== 'range') {
            this.datePicker.api.open();
        } else {
            this.isRangePopupOpened = true;
        }
    }

    datePickerChangeStart(value) {
        if (value) {
            this.setStart(value);
        }
    }

    datePickerSelectStart(value) {
        if (value.date) {
            this.setStart(value.date);
        }
    }

    datePickerChangeEnd(value) {
        if (value) {
            this.setEnd(value);
        }
    }

    datePickerSelectEnd(value) {
        if (value.date) {
            this.setEnd(value.date);
        }
    }

    applyChanges() {
        this.isRangePopupOpened = false;
        this.modeChanged.emit(this.mode);
        this.startChanged.emit(this.start);
        this.endChanged.emit(this.end);
        this.rangeChanged.emit({
            start: this.start,
            end: this.end,
        });

        LocalStorage.getStorage().set(`filterByDateRangeModeIN${window.location.pathname}`, this.mode);
        LocalStorage.getStorage().set(`filterByDateRangeStartIN${window.location.pathname}`, this.start.format('YYYY-MM-DD'));
        LocalStorage.getStorage().set(`filterByDateRangeEndIN${window.location.pathname}`, this.end.format('YYYY-MM-DD'));
    }

    today() {
        this.setEnd(moment().startOf('day'));
    }
}
