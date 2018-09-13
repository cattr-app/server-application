import { Component, ViewChild, OnInit, OnDestroy, DoCheck, KeyValueDiffer, KeyValueDiffers, Output, EventEmitter, TemplateRef } from '@angular/core';

import { ScreenshotsBlock, Screenshot } from '../../../models/screenshot.model';
import { Task } from '../../../models/task.model';
import { Project } from '../../../models/project.model';
import { TimeInterval } from '../../../models/timeinterval.model';

import { ApiService } from '../../../api/api.service';
import { DashboardService } from '../dashboard.service';
import { ScreenshotsService } from '../../screenshots/screenshots.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { TasksService } from '../../tasks/tasks.service';
import { ProjectsService } from '../../projects/projects.service';

import { BsModalService, BsModalRef, ModalDirective } from 'ngx-bootstrap';
import * as moment from 'moment';

type SelectItem = Task & { title: string };
type ProjectWithTasks = Project & { tasks: Task[] };

@Component({
    selector: 'dashboard-screenshotlist',
    templateUrl: './screenshot.list.component.html',
    styleUrls: ['./screenshot.list.component.scss']
})
export class ScreenshotListComponent implements OnInit, DoCheck, OnDestroy {
    @ViewChild('loading') element: any;
    @ViewChild('changeTaskModal') changeTaskModal: TemplateRef<any>;
    @ViewChild('screenshotModal') screenshotModal: ModalDirective;

    @Output() onReload = new EventEmitter<{}>();
    @Output() onFilterChanged = new EventEmitter<string | Task>();

    isLoading = false;

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
    search: string | SelectItem = null;

    newTask: Task = new Task({
        id: 0,
        project_id: null,
        task_name: '',
        description: '',
        active: 1,
    });

    projects: Project[] = [];
    tasks: Task[] = [];
    selectedProject: Project = null;
    selectedTask: Task = null;
    modalRef: BsModalRef;
    modalScreenshot?: Screenshot = null;

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
        protected projectService: ProjectsService,
        protected taskService: TasksService,
        differs: KeyValueDiffers,
        protected modalService: BsModalService,
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
                task['title'] = task.project
                    ? `${task.project.name} - ${task.task_name}`
                    : task.task_name;
                return task;
            });

            this.suggestedTasks = this.availableTasks;
            this.tasks = this.availableTasks;
        }, {
            'with': 'project',
        });

        this.projectService.getItems(result => this.projects = result);

        this.newTask.user_id = this.api.getUser().id;
        this.newTask.assigned_by = this.newTask.user_id;
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

    reload() {
        // Reload screenshots.
        this.blocks = [];
        this.offset = 0;
        this.screenshotLoading = false;
        this.countFail = 0;
        this.selected = {};
        this.loadNext();
        this.onReload.emit();
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
        this.isLoading = true;

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
            this.reload();
            this.isLoading = false;
        });
    }

    onSearch(event) {
        this.suggestedTasks = this.availableTasks.filter(task => {
            const title = task.title.toLowerCase();
            const query = event.query.toLowerCase();
            return title.indexOf(query) !== -1;
        });
    }

    onSearchChanged() {
        this.onFilterChanged.emit(this.search);
    }

    changeTimeIntervalTask(interval: TimeInterval, taskId: number): Promise<{}> {
        return new Promise((resolve, reject) => {
            try {
                this.timeIntervalsService.editItem(interval.id, {
                    task_id: taskId,
                    user_id: interval.user_id,
                    // ATOM format required by backend.
                    start_at: moment.utc(interval.start_at).format('YYYY-MM-DD[T]HH:mm:ssZ'),
                    end_at: moment.utc(interval.end_at).format('YYYY-MM-DD[T]HH:mm:ssZ'),
                }, () => resolve());
            } catch(e) {
                reject(e);
            }
        });
    }

    onAddTask() {
        this.modalRef.hide();
        this.isLoading = true;

        // Create new task.
        this.taskService.createItem({
            'project_id': this.newTask.project_id,
            'task_name': this.newTask.task_name,
            'description': this.newTask.description,
            'active': this.newTask.active,
            'user_id': this.newTask.user_id,
            'assigned_by': this.newTask.assigned_by,
        }, result => {
            const newTaskId = +result.res.id;

            // Get time intervals of selected screenshots.
            const time_intervals = this.getSelectedScreenshots()
                .map(screenshot => screenshot.time_interval);

            // Edit intervals.
            const results = time_intervals.map(interval =>
                this.changeTimeIntervalTask(interval, newTaskId));

            Promise.all(results).then(() => {
                this.reload();
                this.isLoading = false;
                this.newTask.task_name = '';
                this.newTask.description = '';
            });
        });
    }

    onChangeTask() {
        this.modalRef.hide();
        this.isLoading = true;

        // Get time intervals of selected screenshots.
        const time_intervals = this.getSelectedScreenshots()
            .map(screenshot => screenshot.time_interval);

        // Edit intervals.
        const results = time_intervals.map(interval =>
            this.changeTimeIntervalTask(interval, this.selectedTask.id));

        Promise.all(results).then(() => {
            this.reload();
            this.isLoading = false;
            this.selectedProject = null;
            this.selectedTask = null;
        });
    }

    formatTime(datetime: string) {
        return moment.utc(datetime).local().format('HH:mm');
    }

    openModal(modal: TemplateRef<any>) {
        this.modalRef = this.modalService.show(modal);
    }

    openScreenshotModal(screenshot: Screenshot) {
        this.modalScreenshot = screenshot;
        this.screenshotModal.show();
    }

    changeProject() {
        this.selectedTask = null;

        if (!this.selectedProject) {
            this.tasks = this.availableTasks;
        } else {
            this.taskService.getItems(result => this.tasks = result, {
                'project_id': this.selectedProject.id,
            });
        }
    }

    changeTask() {
        if (this.selectedTask && this.selectedTask.project_id) {
            this.selectedProject = this.projects.find(project =>
                +project.id === +this.selectedTask.project_id);
        }
    }
}
