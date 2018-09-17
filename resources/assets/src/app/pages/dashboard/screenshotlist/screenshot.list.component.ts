import { Component, ViewChild, OnInit, OnDestroy, DoCheck, KeyValueDiffer, KeyValueDiffers, Output, EventEmitter, TemplateRef } from '@angular/core';

import { ScreenshotsBlock, Screenshot } from '../../../models/screenshot.model';
import { TimeInterval } from '../../../models/timeinterval.model';

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
    screenshotLoading = false;
    scrollHandler: any = null;
    countFail = 0;

    selected: { [key: number]: boolean } = {};
    selectedDiffer: KeyValueDiffer<number, boolean> = null;
    selectedIntervals: TimeInterval[] = [];

    modalScreenshot?: Screenshot = null;

    constructor(
        protected api: ApiService,
        protected dashboardService: DashboardService,
        protected screenshotService: ScreenshotsService,
        protected timeIntervalsService: TimeIntervalsService,
        protected projectService: ProjectsService,
        protected taskService: TasksService,
        differs: KeyValueDiffers,
        protected modalService: BsModalService,
    ) {
        this.selectedDiffer = differs.find(this.selected).create();
    }

    getVisibleScreenshots() {
        return this.blocks
            .map(block => block.screenshots
                // Get first screenshot in each group.
                .map(screenshots => screenshots[0])
                // Remove empty entries.
                .filter(screenshot => screenshot))
            // Flatten screenshot blocks.
            .reduce((arr, curr) => arr.concat(curr), []);
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
    }

    ngDoCheck() {
        const selectedChanged = this.selectedDiffer.diff(this.selected);
        if (selectedChanged) {
            this.selectedIntervals = this.getSelectedScreenshots()
                // Get time intervals of the selected screenshots.
                .map(screenshot => screenshot.time_interval);
            this.onSelectionChanged.emit(this.selectedIntervals);
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

    reload() {
        // Reload screenshots.
        this.blocks = [];
        this.offset = 0;
        this.screenshotLoading = false;
        this.countFail = 0;
        this.selected = {};
        this.loadNext();
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

    openScreenshotModal(screenshot: Screenshot) {
        this.modalScreenshot = screenshot;
        this.screenshotModal.show();
    }

    prevScreenshot() {
        const screenshots = this.getVisibleScreenshots();
        const currentIndex = screenshots
            .findIndex(screenshot => screenshot.id === this.modalScreenshot.id);
        if (currentIndex > 0) {
            this.modalScreenshot = screenshots[currentIndex - 1];
        }
    }

    nextScreenshot() {
        const screenshots = this.getVisibleScreenshots();
        const currentIndex = screenshots
            .findIndex(screenshot => screenshot.id === this.modalScreenshot.id);
        if (currentIndex !== -1 && currentIndex < screenshots.length - 1) {
            this.modalScreenshot = screenshots[currentIndex + 1];
        }
    }

    formatTime(datetime: string) {
        return moment.utc(datetime).local().format('HH:mm');
    }
}
