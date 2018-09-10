import {Component, ViewChild, OnInit, OnDestroy, DoCheck,  KeyValueDiffer, KeyValueDiffers} from '@angular/core';

import {ScreenshotsBlock} from '../../../models/screenshot.model';
import { Task } from '../../../models/task.model';


import {ApiService} from '../../../api/api.service';
import {DashboardService} from '../dashboard.service';
import { ScreenshotsService } from '../../screenshots/screenshots.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { TasksService } from '../../tasks/tasks.service';

import * as moment from 'moment';

type SelectItem = Task & { title: string };

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

    availableTasks: SelectItem[] = [];
    suggestedTasks: SelectItem[] = [];
    search: SelectItem = null;

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
        protected screenshotService: ScreenshotsService,
        protected timeIntervalsService: TimeIntervalsService,
        protected taskService: TasksService,
        differs: KeyValueDiffers,
    ) {
        this.selectedDiffer = differs.find(this.selected).create();
    }

    getSelectedScreenshots() {
        // Get selected screenshots.
        return this.blocks
            .map(block => {
                return block.screenshots
                    // Get screenshot groups where some screenshots is selected.
                    .filter(screenshots => screenshots.some(screenshot => this.selected[screenshot.id]))
                    // Flatten screenshot groups.
                    .reduce((arr, screenshots) => arr.concat(screenshots), []);
            })
            // Flatten screenshot blocks.
            .reduce((arr, curr) => arr.concat(curr), []);
    }

    ngOnInit() {
        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();

        this.taskService.getItems(result => {
            this.availableTasks = result.map(task => {
                task['title'] = `${task.project.name} - ${task.task_name}`;
                return task;
            });
            this.suggestedTasks = this.availableTasks;
        }, {
            'with': 'project',
        });
    }

    ngDoCheck() {
        const selectedChanged = this.selectedDiffer.diff(this.selected);
        if (selectedChanged) {
            this.selectedTime = this.getSelectedScreenshots()
                // Calculate total time of intervals of the selected screenshots.
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
            .reduce((arr, screenshots) => arr.concat(screenshots), [])
            .filter(screenshot => screenshot.id)
            .map(screenshot => screenshot.id)
            .forEach(id => {
                this.selected[id] = select;
            });
    }

    onDelete() {
        // Get time intervals of selected screenshots.
        const time_intervals = this.getSelectedScreenshots()
            .map(screenshot => screenshot.time_interval);

        // Delete screenshots & intervals.
        const results = time_intervals.map(interval => {
            return new Promise((resolve) => {
                this.timeIntervalsService.removeItem(interval.id, () => resolve());
            });
        });

        Promise.all(results).then(() => {
            // Reload screenshots.
            this.blocks = [];
            this.offset = 0;
            this.countFail = 0;
            this.loadNext();
        });
    }

    onSearch(event) {
        this.suggestedTasks = this.availableTasks.filter(task => {
            const title = task.title.toLowerCase();
            const query = event.query.toLowerCase();
            return title.indexOf(query) !== -1;
        });
    }

    onChange() {
        // Get time intervals of selected screenshots.
        const time_intervals = this.getSelectedScreenshots()
            .map(screenshot => screenshot.time_interval);

        // Edit intervals.
        const results = time_intervals.map(interval => {
            return new Promise((resolve) => {
                interval.task_id = this.search.id;
                this.timeIntervalsService.editItem(interval.id, {
                    task_id: this.search.id,
                    user_id: interval.user_id,
                    // ATOM format required by backend.
                    start_at: moment.utc(interval.start_at).format('YYYY-MM-DD[T]HH:mm:ssZ'),
                    end_at: moment.utc(interval.end_at).format('YYYY-MM-DD[T]HH:mm:ssZ'),
                }, () => resolve());
            });
        });

        Promise.all(results).then(() => {
            // Reload screenshots.
            this.blocks = [];
            this.offset = 0;
            this.loadNext();
        });
    }

    formatTime(datetime: string) {
        return moment.utc(datetime).local().format('HH:mm');
    }
}
