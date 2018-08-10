import { Component, ViewChild, ElementRef, Input, Output, EventEmitter } from '@angular/core';
import { DatePickerDirective } from 'ng2-date-picker';
import * as moment from 'moment';

@Component({
    selector: 'date-selector',
    templateUrl: './date-selector.component.html',
    styleUrls: ['./date-selector.component.scss']
})
export class DateSelectorComponent {
    @ViewChild('dateInput') dateInput: DatePickerDirective;

    @Input() dateFormat: string = 'YYYY-MM-DD';
    @Input() mode: string = 'day';

    @Input() date: moment.Moment = moment.utc();
    @Output() dateChange = new EventEmitter<moment.Moment>();

    protected get _date(): string {
        return this.date.format(this.dateFormat);
    }

    protected set _date(value: string) {
        if (moment(value, this.dateFormat, true).isValid()) {
            if (this.mode === 'day') {
                this.date = moment.utc(value).startOf('day');
            } else if (this.mode === 'week') {
                this.date = moment.utc(value).startOf('week').add(1, 'day');
            } else if (this.mode === 'month') {
                this.date = moment.utc(value).startOf('month');
            }
        }
    }

    protected get _mode(): string {
        return this.mode === 'month' ? 'month' : 'day';
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
