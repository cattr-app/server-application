import {Component, OnInit, OnDestroy} from '@angular/core';
import {ActivatedRoute} from '@angular/router';

import {ApiService} from '../../../api/api.service';
import {ProjectsService} from '../projects.service';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

import {Project} from '../../../models/project.model';
import { Task } from '../../../models/task.model';
import { TimeInterval } from '../../../models/timeinterval.model';

import {ItemsShowComponent} from '../../items.show.component';

import * as moment from 'moment';

type TaskWithIntervals = Task & { time_intervals?: TimeInterval[] };
type ProjectWithTasks = Project & { tasks?: TaskWithIntervals[] };

interface TaskInfo {
    id: number;
    name: string;
    firstActivity: moment.Moment;
    lastActivity: moment.Moment;
    totalTime: number;
}

enum TaskOrder {
    TaskAsc,
    TaskDesc,
    LastActivityAsc,
    LastActivityDesc,
    TotalTimeAsc,
    TotalTimeDesc,
}

@Component({
    selector: 'app-projects-show',
    templateUrl: './projects.show.component.html',
    styleUrls: ['./projects.show.component.scss', '../../items.component.scss']
})
export class ProjectsShowComponent extends ItemsShowComponent implements OnInit, OnDestroy {

    item: ProjectWithTasks = new Project();
    tasks: TaskInfo[] = [];
    order: TaskOrder = TaskOrder.LastActivityDesc;
    firstActivity: moment.Moment;
    lastActivity: moment.Moment;

    constructor(api: ApiService,
                projectService: ProjectsService,
                route: ActivatedRoute,
                allowedService: AllowedActionsService) {
        super(api, projectService, route, allowedService);
    }

    ngOnInit() {
        this.sub = this.route.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), { 'with': 'tasks,tasks.timeIntervals' });
    }

    setItem(project: ProjectWithTasks) {
        super.setItem(project);
        this.tasks = project.tasks.map(task => {
            const time = task.time_intervals.reduce((total, interval) => {
                const start = moment.utc(interval.start_at);
                const end = moment.utc(interval.end_at);
                return total + end.diff(start);
            }, 0);

            const intervals = task.time_intervals.length;
            const firstInterval = intervals > 0 ? task.time_intervals[0] : null;
            const lastInterval = intervals > 0 ? task.time_intervals[intervals - 1] : null;
            const firstActivity = firstInterval !== null ? moment.utc(firstInterval.start_at) : null;
            const lastActivity = lastInterval !== null ? moment.utc(lastInterval.end_at) : null;

            return {
                id: task.id,
                name: task.task_name,
                firstActivity,
                lastActivity,
                totalTime: time,
            }
        });

        this.firstActivity = this.tasks
            .map(task => task.firstActivity)
            .filter(interval => interval)
            .reduce((min, curr) => min.diff(curr) < 0 ? min : curr, moment.utc());

        this.lastActivity = this.tasks
            .map(task => task.lastActivity)
            .filter(interval => interval)
            .reduce((max, curr) => max.diff(curr) > 0 ? max : curr, moment.utc(0));

        this.sort();
    }

    onTableHeaderClick(e: MouseEvent) {
        const column = (e.target as HTMLElement).getAttribute('data-order');
        switch (column) {
            case 'task': {
                const order = this.order === TaskOrder.TaskAsc
                    ? TaskOrder.TaskDesc : TaskOrder.TaskAsc;
                this.sort(order);
                break;
            }

            case 'lastActivity': {
                const order = this.order === TaskOrder.LastActivityDesc
                    ? TaskOrder.LastActivityAsc : TaskOrder.LastActivityDesc;
                this.sort(order);
                break;
            }

            case 'totalTime': {
                const order = this.order === TaskOrder.TotalTimeDesc
                    ? TaskOrder.TotalTimeAsc : TaskOrder.TotalTimeDesc;
                this.sort(order);
                break;
            }

            default:
                break;
        }
    }

    sort(order: TaskOrder = this.order) {
        this.order = order;
        this.tasks = this.tasks.sort((a, b) => {
            switch (this.order) {
                case TaskOrder.TaskAsc:
                    return a.name.localeCompare(b.name);

                case TaskOrder.TaskDesc:
                    return b.name.localeCompare(a.name);

                case TaskOrder.LastActivityAsc: {
                    const aLastActivity = a.lastActivity || moment(0);
                    const bLastActivity = b.lastActivity || moment(0);
                    return aLastActivity.diff(bLastActivity);
                }

                default:
                case TaskOrder.LastActivityDesc: {
                    const aLastActivity = a.lastActivity || moment(0);
                    const bLastActivity = b.lastActivity || moment(0);
                    return bLastActivity.diff(aLastActivity);
                }

                case TaskOrder.TotalTimeAsc:
                    return a.totalTime - b.totalTime;

                case TaskOrder.TotalTimeDesc:
                    return b.totalTime - a.totalTime;
            }
        });
    }

    formatTimeString(time?: moment.Moment) {
        return time !== null ? time.local().format('YYYY-MM-DD HH:mm:ss') : '-';
    }

    formatDurationString(time: number) {
        const duration = moment.duration(time);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }


    cleanupParams() : string[] {
        return [
            'item',
            'tasks',
            'order',
            'firstActivity',
            'lastActivity',
            'api',
            'projectService',
            'router',
            'allowedService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
