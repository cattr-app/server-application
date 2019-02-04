import { Component, ViewChild, OnInit, DoCheck, Output, EventEmitter, TemplateRef, Input, IterableDiffer, IterableDiffers } from '@angular/core';

import { Task } from '../models/task.model';
import { Project } from '../models/project.model';
import { TimeInterval } from '../models/timeinterval.model';

import { ApiService } from '../api/api.service';
import { TimeIntervalsService } from '../pages/timeintervals/timeintervals.service';
import { TasksService } from '../pages/tasks/tasks.service';
import { ProjectsService } from '../pages/projects/projects.service';

import { BsModalService, BsModalRef } from 'ngx-bootstrap';
import * as moment from 'moment';

type SelectItem = (Project | Task) & { title: string };

@Component({
    selector: 'change-task-panel',
    templateUrl: './change-task-panel.component.html',
    styleUrls: ['./change-task-panel.component.scss']
})
export class ChangeTaskPanelComponent implements OnInit, DoCheck {
    @ViewChild('changeTaskModal') changeTaskModal: TemplateRef<any>;

    @Input() timeIntervals: TimeInterval[] = [];

    @Output() onFilterChanged = new EventEmitter<string | Task | Project>();
    @Output() onIntervalsDeleted = new EventEmitter();
    @Output() onIntervalsChanged = new EventEmitter();

    isLoading = false;

    differ: IterableDiffer<TimeInterval> = null;
    totalTime = 0;

    searchAvailable: SelectItem[] = [];
    searchSuggested: SelectItem[] = [];
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

    get selectedTimeStr(): string {
        const duration = moment.duration(this.totalTime);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        const minutesStr = minutes > 9 ? '' + minutes : '0' + minutes;
        return `${hours}:${minutesStr}`;
    }

    constructor(
        protected api: ApiService,
        protected timeIntervalsService: TimeIntervalsService,
        protected projectService: ProjectsService,
        protected taskService: TasksService,
        differs: IterableDiffers,
        protected modalService: BsModalService,
    ) {
        this.differ = differs.find(this.timeIntervals).create();
    }

    ngOnInit() {
        this.taskService.getItems((tasks: Task[]) => {
            this.tasks = tasks;

            this.projectService.getItems((projects: Project[]) => {
                this.searchAvailable = tasks.map(task => {
                    task['title'] = task.project
                        ? `${task.project.name} - ${task.task_name}`
                        : task.task_name;
                    return task as SelectItem;
                }).concat(projects.map(project => {
                    project['title'] = project.name;
                    return project as SelectItem;
                })).sort((a, b) => a.title.localeCompare(b.title));
                this.searchSuggested = this.searchAvailable;
            });
        }, {
            'with': 'project',
        });

        this.projectService.getItems(result => this.projects = result);

        this.newTask.user_id = this.api.getUser().id;
        this.newTask.assigned_by = this.newTask.user_id;
    }

    ngDoCheck() {
        const selectedChanged = this.differ.diff(this.timeIntervals);
        if (selectedChanged) {
            this.totalTime = this.timeIntervals
                // Calculate total time of intervals of the selected items.
                .map(interval => {
                    const start = moment.utc(interval.start_at);
                    const end = moment.utc(interval.end_at);
                    return end.diff(start);
                })
                .reduce((total, curr) => total + curr, 0);
        }
    }

    onDelete() {
        this.isLoading = true;

        // Delete screenshots & intervals.
        const results = this.timeIntervals.map(interval => {
            return new Promise((resolve) => {
                this.timeIntervalsService.removeItem(interval.id, () => resolve());
            });
        });

        Promise.all(results).then(() => {
            this.isLoading = false;
            this.onIntervalsDeleted.emit();
        });
    }

    onSearch(event) {
        this.searchSuggested = this.searchAvailable.filter(task => {
            const title = task.title.toUpperCase();
            const query = event.query.toUpperCase();
            return title.indexOf(query) !== -1;
        });
    }

    onSearchKeyUp(event) {
        if (event.key === 'Enter') {
            this.onFilterChanged.emit(this.search);
        }
    }

    onSearchSelected() {
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

            // Edit intervals.
            const results = this.timeIntervals.map(interval =>
                this.changeTimeIntervalTask(interval, newTaskId));

            Promise.all(results).then(() => {
                this.isLoading = false;
                this.newTask.task_name = '';
                this.newTask.description = '';
                this.onIntervalsChanged.emit();
            });
        });
    }

    onChangeTask() {
        this.modalRef.hide();
        this.isLoading = true;

        // Edit intervals.
        const results = this.timeIntervals.map(interval =>
            this.changeTimeIntervalTask(interval, this.selectedTask.id));

        Promise.all(results).then(() => {
            this.isLoading = false;
            this.selectedProject = null;
            this.selectedTask = null;
            this.onIntervalsChanged.emit();
        });
    }

    formatTime(datetime: string) {
        return moment.utc(datetime).local().format('HH:mm');
    }

    openModal(modal: TemplateRef<any>) {
        this.modalRef = this.modalService.show(modal);
    }

    changeProject() {
        this.selectedTask = null;

        if (!this.selectedProject) {
            this.taskService.getItems(result => this.tasks = result);
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
