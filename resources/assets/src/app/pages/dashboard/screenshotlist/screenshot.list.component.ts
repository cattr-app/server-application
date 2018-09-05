import {Component, ViewChild, OnInit, OnDestroy, DoCheck,  KeyValueDiffer, KeyValueDiffers} from '@angular/core';

import {ScreenshotsBlock} from '../../../models/screenshot.model';

import {ApiService} from '../../../api/api.service';
import {DashboardService} from '../dashboard.service';

import * as moment from 'moment';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';

@Component({
  selector: 'dashboard-screenshotlist',
  templateUrl: './screenshot.list.component.html',
  styleUrls: ['./screenshot.list.component.scss']
})
export class ScreenshotListComponent implements OnInit, DoCheck, OnDestroy {

    @ViewChild('loading') element: any;
    chunksize = 32;
    offset = 0;
    blocks: ScreenshotsBlock[] = [];
    screenshotLoading = false;
    scrollHandler: any = null;
    countFail = 0;

    selected: { [key: number]: boolean } = {};
    selectedDiffer: KeyValueDiffer<number, boolean> = null;
    selectedTime = 0;

    search = '';

    get selectedTimeStr(): string {
        const duration = moment.duration(this.selectedTime);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        const minutesStr = minutes > 9 ? '' + minutes : '0' + minutes;
        return `${hours}:${minutesStr}`;
    }

    constructor(
        protected api: ApiService,
        protected dashboardService: DashboardService,
        protected timeIntervalsService: TimeIntervalsService,
        differs: KeyValueDiffers,
    ) {
        this.selectedDiffer = differs.find(this.selected).create();
    }

    ngOnInit() {
        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngDoCheck() {
        const selectedChanged = this.selectedDiffer.diff(this.selected);
        if (selectedChanged) {
            this.selectedTime = this.blocks
                // Get selected screenshots.
                .map(block => {
                    return block.screenshots.filter(screenshot => {
                        return screenshot.id && this.selected[screenshot.id];
                    });
                })
                .reduce((arr, curr) => arr.concat(curr), [])
                // Calculate total time of intervals of selected screenshots.
                .map(screenshot => {
                    const interval = screenshot.time_interval;
                    const start = moment.utc(interval.start_at);
                    const end = moment.utc(interval.end_at);
                    return end.diff(start);
                })
                .reduce((total, curr) => total + curr, 0);
        }
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    loadNext() {
        if (this.screenshotLoading || this.countFail > 3) {
          return;
        }

        const user: any = this.api.getUser() ? this.api.getUser() : null;
        this.screenshotLoading = true;
        this.dashboardService.getScreenshots(
            this.chunksize,
            this.offset,
            this.setData.bind(this),
            user.id
        );
    }

    setData(result) {
        if (result.length > 0) {
            this.offset += this.chunksize;

            for (const block of result) {
                this.blocks.push(new ScreenshotsBlock(block));
            }
        } else {
            this.countFail += 1;
        }

        this.screenshotLoading = false;
    }

    onScrollDown() {
        const block_Y_position = this.element.nativeElement.offsetTop;
        const scroll_Y_top_position = window.scrollY;
        const windowHeight = window.innerHeight;
        const bottom_scroll_Y_position = scroll_Y_top_position + windowHeight;

        if (bottom_scroll_Y_position < block_Y_position) {
            // loading new screenshots doesn't needs
            return;
        }

        this.loadNext();
    }

    onSelectBlock(block: ScreenshotsBlock, select: boolean) {
        block.screenshots
            .filter(screenshot => screenshot.id)
            .map(screenshot => screenshot.id)
            .forEach(id => {
                this.selected[id] = select;
            });
    }

    onDelete() {
        const selectedScreenshotIds = Object.keys(this.selected);
        this.blocks
            .map(block => {
                return block.screenshots.filter(screenshot =>
                    selectedScreenshotIds.includes('' + screenshot.id));
            });
        //console.log(ids);

        //this.timeIntervalsService.removeItem();
    }

    onChange() {
        console.log(this.search);
    }

    formatTime(datetime: string) {
        return moment.utc(datetime).local().format('HH:mm');
    }
}
