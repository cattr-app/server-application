import { Component, Input, Output, EventEmitter } from '@angular/core';
import * as moment from 'moment';
import { LocalStorage } from '../../../../api/storage.model';

@Component({
    selector: 'date-range-selector',
    templateUrl: './date-range-selector.component.html',
    styleUrls: ['./date-range-selector.component.scss']
})
export class DateRangeSelectorComponent {
    @Input() dateFormat: string = 'YYYY-MM-DD';
    @Input() mode: string = 'day';

    @Input() startDate: moment.Moment = moment.utc();
    @Output() startDateChange = new EventEmitter<moment.Moment>();

    @Input() endDate: moment.Moment = moment.utc().add(1, 'day');
    @Output() endDateChange = new EventEmitter<moment.Moment>();

    @Output() onApply = new EventEmitter<{}>();

    @Input() isOpened: boolean = true;

    readonly max: moment.Moment = moment.utc().add(1, 'day');
    filterByDateRange = null;

    get _startDate(): string {
        this.filterByDateRange = LocalStorage.getStorage().get(`filterByDateRangeIN${ window.location.pathname }`)
        if (this.filterByDateRange === null || this.filterByDateRange['start'] === undefined ) {
            return this.startDate.format(this.dateFormat);
        } else {
            return moment(this.filterByDateRange['start']).format(this.dateFormat); 
        }
    }

    set _startDate(value: string) {
        if (moment(value, this.dateFormat, true).isValid()) {
            this.startDate = moment.utc(value).startOf('day');
        }
    }

    get _endDate(): string {
        this.filterByDateRange = LocalStorage.getStorage().get(`filterByDateRangeIN${ window.location.pathname }`)
        if (this.filterByDateRange === null || this.filterByDateRange['end'] === undefined ) {
            return this.startDate.format(this.dateFormat);
        } else {
            return moment(this.filterByDateRange['end']).format(this.dateFormat); 
        }
    }

    set _endDate(value: string) {
        if (moment(value, this.dateFormat, true).isValid()) {
            this.endDate = moment.utc(value).add(1, 'day').startOf('day');
        }
    }

    open() {
        this.isOpened = true;
    }

    close() {
        this.isOpened = false;
    }

    toggle() {
        this.isOpened = !this.isOpened;
    }

    apply() {
        this.close();
        this.startDateChange.emit(this.startDate);
        this.endDateChange.emit(this.endDate);
        this.onApply.emit();
    }
}
