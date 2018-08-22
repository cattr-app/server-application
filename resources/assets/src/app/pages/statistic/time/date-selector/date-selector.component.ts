import { Component, ViewChild, ElementRef, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { DatePickerComponent } from 'ng2-date-picker';
import * as moment from 'moment';

@Component({
    selector: 'date-selector',
    templateUrl: './date-selector.component.html',
    styleUrls: ['./date-selector.component.scss']
})
export class DateSelectorComponent implements OnInit {
    @ViewChild('dateInput') dateInput: ElementRef;
    @ViewChild('datePicker') datePicker: DatePickerComponent;

    @Input() dateFormat: string = 'YYYY-MM-DD';
    @Input() mode: string = 'day';

    @Input() date: moment.Moment = moment.utc();
    @Output() dateChange = new EventEmitter<moment.Moment>();

    readonly max: moment.Moment = moment.utc().add(1, 'day');

    get _date(): string {
        return this.date.format(this.dateFormat);
    }

    set _date(value: string) {
        if (moment(value, this.dateFormat, true).isValid()) {
            let date = moment.utc(value, this.dateFormat).startOf('day');
            if (date.diff(this.max) > 0) {
                date = this.max.clone();
            }

            if (this.mode === 'day') {
                this.date = date;
            } else if (this.mode === 'week') {
                this.date = date.startOf('week').add(1, 'day');
            } else if (this.mode === 'month') {
                this.date = date.startOf('month');
            }
        }
    }

    get _inputDate(): string {
        if (this.mode === 'week') {
            return this.date.format('YYYY-MM-DD') + ' - ' + this.date.clone().add(6, 'days').format('YYYY-MM-DD');
        } else if (this.mode === 'month') {
            return this.date.format('YYYY MMMM');
        } else {
            return this.date.format(this.dateFormat);
        }
    }

    set _inputDate(value: string) {
        if (this.mode === 'day') {
            if (moment(value, this.dateFormat, true).isValid()) {
                let date = moment.utc(value, this.dateFormat).startOf('day');
                if (date.diff(this.max) > 0) {
                    date = this.max.clone();
                }
                this.date = date;
            }
        } else if (this.mode === 'week') {
            const val = value.split(' - ')[0];
            if (moment(val, this.dateFormat, true).isValid()) {
                let date = moment.utc(val, this.dateFormat).startOf('day');
                if (date.diff(this.max) > 0) {
                    date = this.max.clone();
                }
                this.date = date.startOf('week').add(1, 'day');
            }
        } else if (this.mode === 'month') {
            if (moment(value, 'YYYY MMMM', true).isValid()) {
                let date = moment.utc(value, 'YYYY MMMM').startOf('day');
                if (date.diff(this.max) > 0) {
                    date = this.max.clone();
                }
                this.date = date.startOf('month');
            }
        }
    }

    get _mode(): string {
        return this.mode === 'month' ? 'month' : 'day';
    }

    get endDate(): string {
        if (this.mode === 'week') {
            return '&ndash; ' + this.date.clone().add(6, 'days').format(this.dateFormat);
        } else {
            return '';
        }
    }

    ngOnInit() {
        this.datePicker.appendToElement = this.dateInput.nativeElement;
    }

    change(value) {
        this.dateChange.emit(this.date);
    }

    prev() {
        if (this.mode === 'day') {
            this.date = this.date.clone().subtract(1, 'day').startOf('day');
        } else if (this.mode === 'week') {
            this.date = this.date.clone().subtract(1, 'week').startOf('week').add(1, 'day');
        } else if (this.mode === 'month') {
            this.date = this.date.clone().subtract(1, 'month').startOf('month');
        }

        this.dateChange.emit(this.date);
    }

    next() {
        if (this.mode === 'day') {
            this.date = this.date.clone().add(1, 'day').startOf('day');
        } else if (this.mode === 'week') {
            this.date = this.date.clone().add(1, 'week').startOf('week').add(1, 'day');
        } else if (this.mode === 'month') {
            this.date = this.date.clone().add(1, 'month').startOf('month');
        }

        this.dateChange.emit(this.date);
    }
}
