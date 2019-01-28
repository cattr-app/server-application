import { Injectable } from '@angular/core';

import { ApiService } from '../../../api/api.service';
import { ItemsService } from '../../items.service';
import { ProjectsService } from '../../projects/projects.service';
import { TasksService } from '../../tasks/tasks.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';

import { Project } from '../../../models/project.model';
import { Task } from '../../../models/task.model';
import { TimeDuration } from '../../../models/timeduration.model';
import { TimeInterval } from '../../../models/timeinterval.model';

import * as moment from 'moment';
import 'moment-timezone';
import { Moment } from 'moment';
import { EventObjectInput } from 'fullcalendar';

@Injectable()
export class TimeDurationService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'time-duration';
    }

    convertFromApi(itemFromApi) {
        return new TimeDuration(itemFromApi);
    }

}

@Injectable()
export class StatisticTimeService {
    protected readonly eventsCache: { [key: string]: EventObjectInput[] } = {};

    constructor(
        private timeintervalService: TimeIntervalsService,
        private timeDurationService: TimeDurationService,
        private taskService: TasksService,
        private projectService: ProjectsService,
    ) { }

    protected loadEvents(timezoneOffset: number, start: Moment, end: Moment): Promise<EventObjectInput[]> {
        const params = {
            'start_at': ['>', start],
            'end_at': ['<', end],
        };

        return new Promise<EventObjectInput[]>((resolve) => {
            this.timeintervalService.getItems((intervals: TimeInterval[]) => {
                const offset = timezoneOffset;
                const events = intervals
                    .map(interval => {
                        const start = moment.utc(interval.start_at).add(offset, 'minutes');
                        const end = moment.utc(interval.end_at).add(offset, 'minutes');
                        return {
                            id: interval.id,
                            title: '',
                            resourceId: interval.user_id,
                            start: start,
                            end: end,
                            task_id: interval.task_id,
                            duration: end.diff(start),
                        } as EventObjectInput;
                    })
                    // Filter events with duration less than one second.
                    // Zero-duration events breaks fullcalendar.
                    .filter(event => event.duration > 1000);

                resolve(events);
            }, params);
        });
    }

    async getEvents(timezoneOffset: number, start: Moment, end: Moment): Promise<EventObjectInput[]> {
        const startStr = start.format('YYYY-MM-DD');
        const endStr = start.format('YYYY-MM-DD');
        const key = `${startStr}-${endStr}`;
        if (!this.eventsCache[key]) {
            this.eventsCache[key] = await this.loadEvents(timezoneOffset, start, end);
        }

        return this.eventsCache[key];
    }

    getDays(timezoneOffset: number, uids: number[], start: Moment, end: Moment): Promise<EventObjectInput[]> {
        const params = {
            'start_at': start,
            'end_at': end,
            'uids': uids,
        };

        return new Promise<EventObjectInput[]>((resolve) => {
            this.timeDurationService.getItems((durations: TimeDuration[]) => {
                const offset = timezoneOffset;
                const events = durations.map(duration => {
                    const start = moment.utc(duration.date).add(offset, 'minutes');
                    const end = start.clone().add(duration.duration, 'seconds');
                    return {
                        title: '',
                        resourceId: duration.user_id,
                        start: start,
                        end: end,
                        duration: end.diff(start),
                    } as EventObjectInput;
                });
                resolve(events);
            }, params);
        });
    }

    getTasks(ids) {
        const params = {
            'id': ['=', ids],
        };

        return new Promise<Task[]>(resolve => {
            this.taskService.getItems((tasks: Task[]) => {
                resolve(tasks);
            }, params);
        });
    }

    getProjects(ids) {
        const params = {
            'id': ['=', ids],
        };

        return new Promise<Project[]>(resolve => {
            this.projectService.getItems((projects: Project[]) => {
                resolve(projects);
            }, params);
        });
    }
}
