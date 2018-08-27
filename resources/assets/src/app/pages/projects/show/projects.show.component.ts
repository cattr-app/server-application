import {Component, OnInit} from '@angular/core';
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
    lastActivity: moment.Moment;
    totalTime: number;
}

@Component({
    selector: 'app-projects-show',
    templateUrl: './projects.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ProjectsShowComponent extends ItemsShowComponent implements OnInit {

    item: ProjectWithTasks = new Project();
    tasks: TaskInfo[] = [];

    constructor(api: ApiService,
                projectService: ProjectsService,
                router: ActivatedRoute,
                allowedService: AllowedActionsService) {
        super(api, projectService, router, allowedService);
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), { 'with': 'tasks,tasks.timeIntervals' });
    }

    setItem(project: ProjectWithTasks) {
        super.setItem(project);
        const tasks = project.tasks.map(task => {
            const time = task.time_intervals.reduce((total, interval) => {
                const start = moment.utc(interval.start_at);
                const end = moment.utc(interval.end_at);
                return total + end.diff(start);
            }, 0);

            const intervals = task.time_intervals.length;
            const lastInterval = intervals > 0 ? task.time_intervals[intervals - 1] : null;
            const lastActivity = lastInterval !== null ? moment.utc(lastInterval.end_at) : null;

            return {
                id: task.id,
                name: task.task_name,
                lastActivity: lastActivity,
                totalTime: time,
            }
        });
        this.tasks = tasks.sort((a, b) => {
            if (a.lastActivity === null && b.lastActivity === null) {
                return 0;
            } else if (a.lastActivity === null && b.lastActivity !== null) {
                return 1;
            } else if (a.lastActivity !== null && b.lastActivity === null) {
                return -1;
            } else {
                return b.lastActivity.diff(a.lastActivity);
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
}
