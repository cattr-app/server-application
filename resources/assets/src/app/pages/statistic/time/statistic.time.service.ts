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

interface Interval {
    id: number;
    task_id: number;
    project_id: number;
    duration: number;
    start_at: string;
    end_at: string;
}

interface UserIntervals {
    user_id: number;
    intervals: Interval[];
    duration: number;
}

interface DashboardIntervals {
    userIntervals: {
        [user_id: number]: UserIntervals;
    };
}

interface UserEvents {
    events: EventObjectInput[];
}

interface DashboardEvents {
    userEvents: {
        [user_id: number]: UserEvents;
    };
}

@Injectable()
export class StatisticTimeService {
    protected readonly intervalsCache: { [date: string]: DashboardIntervals } = {};
    protected readonly eventsCache: { [date: string]: DashboardEvents } = {};
    protected readonly taskCache: { [id: number]: Task } = {};
    protected readonly projectCache: { [id: number]: Project } = {};

    constructor(
        private api: ApiService,
        private timeIntervalService: TimeIntervalsService,
        private timeDurationService: TimeDurationService,
        private taskService: TasksService,
        private projectService: ProjectsService,
    ) { }

    protected fetchIntervals(callback, params?: any) {
        return this.api.send(
            'time-intervals/dashboard',
            params ? params : [],
            result => callback(result),
        );
    }

    protected loadIntervals(uids: number[], date: Moment, forceReload: boolean = false) {
        return new Promise<DashboardIntervals>((resolve) => {
            const dateStr = date.format('YYYY-MM-DD');
            const dates = {
                'start_at': date,
                'end_at': date.clone().add(1, 'day'),
            };
            const loaded = this.intervalsCache[dateStr];
            if (forceReload || !loaded) {
                const params = {
                    ...dates,
                    'user_ids': uids,
                };
                this.fetchIntervals((report: DashboardIntervals) => {
                    this.intervalsCache[dateStr] = report;
                    resolve(report);
                }, params);
            } else {
                const loadedUids = Object.keys(loaded.userIntervals);
                const notLoadedUids = uids.filter(uid => loadedUids.indexOf(uid.toString()) === -1);

                // If intervals for all users are loaded.
                if (!notLoadedUids.length) {
                    resolve(loaded);
                    return;
                }

                const params = {
                    ...dates,
                    'user_ids': notLoadedUids,
                };
                this.fetchIntervals((report: DashboardIntervals) => {
                    this.intervalsCache[dateStr].userIntervals = {
                        ...loaded.userIntervals,
                        ...report.userIntervals,
                    };

                    resolve(this.intervalsCache[dateStr]);
                }, params);
            }
        });
    }

    intervalsToEvents(group: UserIntervals, timezoneOffset: number): UserEvents {
        const events = group.intervals
            .map(interval => {
                const start = moment.utc(interval.start_at).add(timezoneOffset, 'minutes');
                const end = moment.utc(interval.end_at).add(timezoneOffset, 'minutes');
                return {
                    id: interval.id,
                    resourceId: group.user_id,
                    task_id: interval.task_id,
                    project_id: interval.project_id,
                    interval_ids: [
                        interval.id,
                    ],
                    start: start,
                    end: end,
                    duration: interval.duration,
                    title: '',
                } as EventObjectInput;
            })
            .reduce((events, current) => {
                if (!events.length) {
                    return [current];
                }

                const last = events[events.length - 1];
                if ((current.start as Moment).diff(last.end) <= 1000
                    && current.task_id === last.task_id) {
                    events[events.length - 1] = {
                        ...last,
                        interval_ids: [
                            ...last.interval_ids,
                            ...current.interval_ids,
                        ],
                        end: current.end,
                        duration: last.duration + current.duration,
                    };
                } else {
                    events.push(current);
                }

                return events;
            }, [])
            .filter(event => event.duration > 1000);
        return { events };
    }

    async getEvents(
        timezoneOffset: number,
        uids: number[],
        date: Moment,
        forceReload: boolean = false,
    ): Promise<EventObjectInput[]> {
        const intervals = await this.loadIntervals(uids, date, forceReload);
        return uids.map(uid => {
            const intervalGroup = intervals.userIntervals[uid];
            if (!intervalGroup) {
                return [];
            }

            const dateStr = date.format('YYYY-MM-DD');
            if (!this.eventsCache[dateStr]) {
                this.eventsCache[dateStr] = {
                    userEvents: [],
                };
            }

            if (forceReload || !this.eventsCache[dateStr].userEvents[uid]) {
                this.eventsCache[dateStr].userEvents[uid] = this.intervalsToEvents(intervalGroup, timezoneOffset);
            }

            return this.eventsCache[dateStr].userEvents[uid].events;
        }).reduce((intervals, userIntervals) => {
            return intervals.concat(userIntervals);
        }, []);
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

    async getTasks(ids: number[]) {
        const loadedIds = Object.keys(this.taskCache);
        const notLoadedIds = ids.filter(id => id && loadedIds.indexOf(id.toString()) === -1);
        if (!notLoadedIds.length) {
            return ids
                .map(id => this.taskCache[id])
                .filter(task => task);
        }

        const params = {
            'id': ['=', notLoadedIds],
        };
        const tasks = await new Promise<Task[]>(resolve => {
            this.taskService.getItems((tasks: Task[]) => {
                resolve(tasks);
            }, params);
        });
        tasks.forEach(task => this.taskCache[task.id] = task);
        return ids
            .map(id => this.taskCache[id])
            .filter(task => task);
    }

    async getProjects(ids: number[]) {
        const loadedIds = Object.keys(this.projectCache);
        const notLoadedIds = ids.filter(id => id && loadedIds.indexOf(id.toString()) === -1);
        if (!notLoadedIds.length) {
            return ids
                .map(id => this.projectCache[id])
                .filter(proj => proj);
        }

        const params = {
            'id': ['=', notLoadedIds],
        };
        const projects = await new Promise<Project[]>(resolve => {
            this.projectService.getItems((projects: Project[]) => {
                resolve(projects);
            }, params);
        });
        projects.forEach(proj => this.projectCache[proj.id] = proj);
        return ids
            .map(id => this.projectCache[id])
            .filter(proj => proj);
    }
}
