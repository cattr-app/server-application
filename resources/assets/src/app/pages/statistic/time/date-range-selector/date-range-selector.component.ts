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

    @Input() endDate: moment.Moment = moment.utc().add(1, 'day');
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

            const max = this.endDate.clone().subtract(1, 'day');
            if (date.diff(max) > 0) {
                this.startDate = max;
            } else {
                this.startDate = date;
            }
        }
    }

    protected get _endDate(): string {
        return this.endDate.clone().subtract(1, 'day').format(this.dateFormat);
    }

    protected set _endDate(value: string) {
        if (moment(value, this.dateFormat, true).isValid()) {
            let date = moment.utc(value).startOf('day').add(1, 'day');
            const max = this.max.clone().add(1, 'day');
            if (date.diff(max) > 0) {
                date = max;
            }

            const min = this.startDate.clone().add(1, 'day');
            if (date.diff(min) < 0) {
                this.endDate = min;
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
