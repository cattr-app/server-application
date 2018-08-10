import { Component, Output, EventEmitter, ViewChild } from '@angular/core';
import { DateRangeSelectorComponent } from '../date-range-selector/date-range-selector.component';
import * as moment from 'moment';

@Component({
    selector: 'view-switcher',
    templateUrl: './view-switcher.component.html',
    styleUrls: ['./view-switcher.component.scss']
})
export class ViewSwitcherComponent {
    @ViewChild('dateRangeSelector') dateRangeSelector: DateRangeSelectorComponent;

    start: moment.Moment = moment.utc();
    end: moment.Moment = moment.utc();

    userInteraction: boolean = false;
    viewName: string = 'timelineDay';
    activeButton: string = 'timelineDay';

    @Output() setView = new EventEmitter<{}>();

    changeDate(date: moment.Moment) {
        this.start = date;
        this.end = date;
        this.setView.emit({
            view: this.viewName,
            start: this.start,
            end: this.end
        });
    }

    changeRange(start: moment.Moment, end: moment.Moment) {
        this.start = start;
        this.end = end;
        this.setView.emit({
            view: this.viewName,
            start: this.start,
            end: this.end
        });
    }

    changeViewName(viewName: string) {
        this.activeButton = viewName;
        this.viewName = viewName;
        this.setView.emit({
            view: this.viewName,
            start: this.start,
            end: this.end
        });
    }

    /*showRangeSelector() {
        this.activeButton = 'timelineRange';
        this.dateRangeSelector.open();
    }*/
}
