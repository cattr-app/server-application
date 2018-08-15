import { Component, ViewChild, ElementRef, Input, Output, EventEmitter } from '@angular/core';
import { DatePickerDirective } from 'ng2-date-picker';
import * as moment from 'moment';

@Component({
    selector: 'date-range-selector',
    templateUrl: './date-range-selector.component.html',
    styleUrls: ['./date-range-selector.component.scss']
})
export class DateRangeSelectorComponent {
    @ViewChild('startDateInput') startDateInput: DatePickerDirective;
    @ViewChild('endDateInput') endDateInput: DatePickerDirective;

    @Input() dateFormat: string = 'YYYY-MM-DD';
    @Input() mode: string = 'day';

    @Input() startDate: moment.Moment = moment.utc();
    @Output() startDateChange = new EventEmitter<moment.Moment>();

    @Input() endDate: moment.Moment = moment.utc();
    @Output() endDateChange = new EventEmitter<moment.Moment>();

    readonly max: moment.Moment = moment.utc().add(1, 'day');

    protected get _startDate(): string {
        return this.startDate.format(this.dateFormat);
    }

    protected set _startDate(value: string) {
        if (moment(value, this.dateFormat, true).isValid()) {
            let date = moment.utc(value).startOf('day');
            if (date.diff(this.max) > 0) {
                date = this.max.clone();
            }

            if (date.diff(this.endDate) > 0) {
                this.startDate = this.endDate.clone();
            } else {
                this.startDate = date;
            }
        }
    }

    protected get _endDate(): string {
        return this.endDate.format(this.dateFormat);
    }

    protected set _endDate(value: string) {
        if (moment(value, this.dateFormat, true).isValid()) {
            let date = moment.utc(value).startOf('day');
            if (date.diff(this.max) > 0) {
                date = this.max.clone();
            }

            if (date.diff(this.startDate) < 0) {
                this.endDate = this.startDate.clone();
            } else {
                this.endDate = date;
            }
        }
    }

    changeStartDate(value) {
        this.startDateChange.emit(this.startDate);
        this.endDateChange.emit(this.endDate);
    }

    changeEndDate(value) {
        this.startDateChange.emit(this.startDate);
        this.endDateChange.emit(this.endDate);
    }

    open() {
        this.startDateInput.api.open();
        this.endDateInput.api.open();
    }

    close() {
        this.startDateInput.api.close();
        this.endDateInput.api.close();
    }
}
