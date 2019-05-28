import { Component, ViewChild, OnInit, OnDestroy, DoCheck, KeyValueDiffer, KeyValueDiffers, Output, EventEmitter, TemplateRef } from '@angular/core';

import { ScreenshotsBlock, Screenshot } from '../../../models/screenshot.model';
import { TimeInterval } from '../../../models/timeinterval.model';
import { Task } from '../../../models/task.model';
import { Project } from '../../../models/project.model';

import { ApiService } from '../../../api/api.service';
import { DashboardService } from '../dashboard.service';
import { ScreenshotsService } from '../../screenshots/screenshots.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { TasksService } from '../../tasks/tasks.service';
import { ProjectsService } from '../../projects/projects.service';

import { BsModalService, ModalDirective } from 'ngx-bootstrap';
import * as moment from 'moment';

@Component({
    selector: 'dashboard-screenshotlist',
    templateUrl: './screenshot.list.component.html',
    styleUrls: ['./screenshot.list.component.scss']
})
export class ScreenshotListComponent implements OnInit, DoCheck, OnDestroy {
    @ViewChild('loading') element: any;
    @ViewChild('changeTaskModal') changeTaskModal: TemplateRef<any>;
    @ViewChild('screenshotModal') screenshotModal: ModalDirective;

    @Output() onSelectionChanged = new EventEmitter<TimeInterval[]>();

    chunksize = 32;
    offset = 0;
    blocks: ScreenshotsBlock[] = [];
    filteredBlocks: ScreenshotsBlock[] = [];
    screenshotLoading = false;
    scrollHandler: any = null;
    countFail = 0;

    selected: { [key: number]: boolean } = {};
    selectedDiffer: KeyValueDiffer<number, boolean> = null;
    selectedIntervals: TimeInterval[] = [];

    _filter: string | Task | Project = '';

    modalScreenshot?: Screenshot = null;

    constructor(
        protected api: ApiService,
        protected dashboardService: DashboardService,
        protected screenshotService: ScreenshotsService,
        protected timeIntervalsService: TimeIntervalsService,
        protected projectService: ProjectsService,
        protected taskService: TasksService,
        protected differs: KeyValueDiffers,
        protected modalService: BsModalService,
    ) {
        this.selectedDiffer = differs.find(this.selected).create();
    }

    getIntervals() {
        return this.blocks
            .map(block => block.intervals
                // Get all interval in each group.
                .reduce((arr, curr) => arr.concat(curr), [])
                // Remove empty entries.
                .filter(interval => interval))
            // Flatten interval blocks.
            .reduce((arr, curr) => arr.concat(curr), []);
    }

    getVisibleIntervals() {
        return this.blocks
            .map(block => block.intervals
                // Get first interval in each group.
                .map(intervals => intervals[0])
                // Remove empty entries.
                .filter(interval => interval))
            // Flatten interval blocks.
            .reduce((arr, curr) => arr.concat(curr), []);
    }

    getSelectedIntervals() {
        // Get selected intervals.
        return this.blocks
            .map(block => {
                return block.intervals
                    // Get interval groups where some intervals is selected.
                    .filter(intervals => intervals.some(interval => this.selected[interval.id]))
                    // Flatten interval groups.
                    .reduce((arr, intervals) => arr.concat(intervals), []);
            })
            // Flatten screenshot blocks.
            .reduce((arr, curr) => arr.concat(curr), []);
    }

    ngOnInit() {
        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngDoCheck() {
        const selectedChanged = this.selectedDiffer.diff(this.selected);
        if (selectedChanged) {
            this.selectedIntervals = this.getSelectedIntervals();
            this.onSelectionChanged.emit(this.selectedIntervals);
        }
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

        this.filter();
        this.screenshotLoading = false;
    }

    reload() {
        // Reload screenshots.
        this.blocks = [];
        this.offset = 0;
        this.screenshotLoading = false;
        this.countFail = 0;
        this.selected = {};
        this.loadNext();
    }

    filter(filter: string | Task | Project = this._filter) {
        this._filter = filter;
        this.filteredBlocks = this.blocks.map(block => {
            const filteredBlock = { ...block };
            filteredBlock.intervals = block.intervals.map(intervals => intervals.filter(interval => {
                if (!interval || !interval.task) {
                    return false;
                }

                if (typeof this._filter === "string") {
                    const filter = this._filter.toLowerCase();
                    const taskName = interval.task.task_name.toLowerCase();
                    const projName = interval.task.project
                        ? interval.task.project.name.toLowerCase() : '';
                    return taskName.indexOf(filter) !== -1
                        || projName.indexOf(filter) !== -1;
                } else if (this._filter instanceof Project) {
                    return +interval.task.project_id === +this._filter.id;
                } else if (this._filter instanceof Task) {
                    return +interval.task.id === +this._filter.id;
                }
            }));
            return filteredBlock;
        }).filter(block => block.intervals.some(group => group.length > 0));
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
        block.intervals
            .reduce((arr, intervals) => arr.concat(intervals), [])
            .filter(interval => interval.id)
            .map(interval => interval.id)
            .forEach(id => {
                this.selected[id] = select;
            });
    }

    openScreenshotModal(screenshot: Screenshot) {
        this.modalScreenshot = screenshot;
        this.screenshotModal.show();
    }

    prevScreenshot() {
        const intervals = this.getVisibleIntervals().filter(interval => interval.screenshots.length);
        const currentIndex = intervals
            .findIndex(interval => +interval.id === +this.modalScreenshot.time_interval_id);
        if (currentIndex > 0) {
            this.modalScreenshot = intervals[currentIndex - 1].screenshots[0];
        }
    }

    nextScreenshot() {
        const intervals = this.getVisibleIntervals().filter(interval => interval.screenshots.length);
        const currentIndex = intervals
            .findIndex(interval => +interval.id === +this.modalScreenshot.time_interval_id);
        if (currentIndex !== -1 && currentIndex < intervals.length - 1) {
            this.modalScreenshot = intervals[currentIndex + 1].screenshots[0];
        }
    }

    deleteScreenshot(screenshot: Screenshot) {
        this.timeIntervalsService.removeItem(screenshot.time_interval_id, () => {
            const intervals = this.getIntervals();
            const currentIndex = intervals
                .findIndex(interval => +interval.id === +this.modalScreenshot.time_interval_id);
            if (currentIndex !== -1 && currentIndex < intervals.length - 1) {
                this.modalScreenshot = intervals[currentIndex + 1].screenshots[0];
            } else if (currentIndex > 0) {
                this.modalScreenshot = intervals[currentIndex - 1].screenshots[0];
            } else {
                this.screenshotModal.hide();
            }

            this.blocks.forEach(block => {
                block.intervals = block.intervals.map(group =>
                    group.filter(scr => +scr.id !== +screenshot.id));
            });

            this.filter();
        });
    }

    formatTime(datetime: string) {
        return moment.utc(datetime).local().format('HH:mm');
    }

    formatDate(datetime?: string) {
        if (!datetime) {
            return null;
        }

        return moment.utc(datetime).local().format('DD.MM.YYYY HH:mm:ss');
    }

    blockTrackFn(i, el: Screenshot) {
        return i;
    }

    screenshotsTrackFn(i, el: Screenshot) {
        return i;
    }


    cleanupParams() : string[] {
        return [
            'element',
            'changeTaskModal',
            'screenshotModal',
            'onSelectionChanged',
            'chunksize',
            'offset',
            'blocks',
            'filteredBlocks',
            'screenshotLoading',
            'scrollHandler',
            'countFail',
            'selected',
            'selectedDiffer',
            'selectedIntervals',
            '_filter',
            'modalScreenshot',
            'api',
            'dashboardService',
            'screenshotService',
            'timeIntervalsService',
            'projectService',
            'taskService',
            'differs',
            'modalService',
        ];
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);

        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
