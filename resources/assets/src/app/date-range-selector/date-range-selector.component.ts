import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';

import * as moment from 'moment';

@Component({
    selector: 'app-date-range-selector',
    templateUrl: './date-range-selector.component.html',
    styleUrls: ['./date-range-selector.component.scss']
})
export class DateRangeSelectorComponent implements OnInit {
    @Input() start: moment.Moment;
    @Input() end: moment.Moment;
    @Input() mode: string = 'day';

    @Output() startChanged = new EventEmitter<moment.Moment>();
    @Output() endChanged = new EventEmitter<moment.Moment>();
    @Output() modeChanged = new EventEmitter<string>();

    // May be different to an active mode.
    // Used to make button active as soon as user presses it.
    activeButton: string = 'day';
    // True, when user presses a button.
    isActive: boolean = false;

    constructor() { }

    ngOnInit() { }

    setStart(start: moment.Moment) {
        this.start = start;
        this.startChanged.emit(this.start);
    }

    setEnd(end: moment.Moment) {
        this.end = end;
        this.endChanged.emit(this.end);
    }

    setMode(mode: string) {
        this.mode = mode;
        this.activeButton = mode;
        this.modeChanged.emit(this.mode);
    }
}
