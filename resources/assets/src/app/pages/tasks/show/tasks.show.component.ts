import { Component, OnInit, OnDestroy, ViewChild, ElementRef, AfterViewInit, ChangeDetectorRef, ViewChildren, QueryList } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

import * as moment from 'moment';

import { ApiService } from '../../../api/api.service';
import { TasksService } from '../tasks.service';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

import { ItemsShowComponent } from '../../items.show.component';

import { Task } from '../../../models/task.model';
import { User } from "../../../models/user.model";
import { TimeInterval } from '../../../models/timeinterval.model';
import { ScreenshotListComponent } from '../../../screenshot-list/screenshot-list.component';

type TaskWithIntervals = Task & { time_intervals?: TimeInterval[] };

interface UserInfo {
    user_id: number;
    user: User;
    time: number;
    perDate: {
        [date: string]: {
            expanded: boolean;
            formattedDate: string;
            time: number;
        }
    }
}

@Component({
    selector: 'app-tasks-show',
    templateUrl: './tasks.show.component.html',
    styleUrls: ['./tasks.show.component.scss', '../../items.component.scss']
})
export class TasksShowComponent extends ItemsShowComponent implements OnInit, OnDestroy, AfterViewInit {
    @ViewChildren('screenshotLists') screenshotLists: QueryList<ScreenshotListComponent>;
    @ViewChild('graphWrapper') graphWrapper: ElementRef;

    item: TaskWithIntervals = new Task();
    users: UserInfo[] = [];
    totalTime: number = 0;

    readonly objectKeys = Object.keys;

    graphSize: number[] = [400, 400];
    graph: any[] = [];
    colorScheme = {
        domain: ['#3097D1'],
    };

    selectedIntervalsByDate: {
        [date: string]: TimeInterval[]
    } = {};
    selectedIntervals: TimeInterval[] = [];

    constructor(
        protected api: ApiService,
        protected taskService: TasksService,
        protected router: ActivatedRoute,
        protected allowService: AllowedActionsService,
        protected cdr: ChangeDetectorRef,
    ) {
        super(api, taskService, router, allowService);
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), {
            'with': 'user,project,assigned,timeIntervals,timeIntervals.user',
        });
    }

    protected updateGraphWidth() {
        const containerWidth = this.graphWrapper.nativeElement.offsetWidth;
        const padding = 150;
        let lineWidth = (containerWidth - padding) / this.graph.length;
        lineWidth = Math.max(Math.min(lineWidth, 80), 20);
        this.graphSize[0] = lineWidth * this.graph.length + padding;
    }

    ngAfterViewInit() {
        this.updateGraphWidth();
    }

    formatDurationString(time: number) {
        const duration = moment.duration(+time);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }

    setItem(result) {
        this.item = result;

        this.users = this.item.time_intervals.reduce((users: UserInfo[], interval) => {
            const index = users.findIndex(u => u.user_id === interval.user_id);
            const end = moment.utc(interval.end_at);
            const start = moment.utc(interval.start_at);
            const time = end.diff(start);
            const date = start.format('DD-MM-YYYY');
            if (index === -1) {
                users.push({
                    user_id: interval.user_id,
                    user: interval.user,
                    time,
                    perDate: {
                        [date]: {
                            expanded: false,
                            formattedDate: start.format('YYYY-MM-DD'),
                            time,
                        },
                    },
                });
            } else {
                users[index].time += time;

                if (users[index].perDate[date]) {
                    users[index].perDate[date].time += time;
                } else {
                    users[index].perDate[date] = {
                        expanded: false,
                        formattedDate: start.format('YYYY-MM-DD'),
                        time,
                    };
                }
            }
            return users;
        }, []);

        this.totalTime = this.users.reduce((sum, userInfo) => {
            return sum + userInfo.time;
        }, 0);

        this.graph = this.item.time_intervals.reduce((graph, interval) => {
            const start = moment.utc(interval.start_at);
            const end = moment.utc(interval.end_at);
            const time = end.diff(start);

            const user_id = interval.user_id;
            const name = interval.user ? interval.user.full_name : '';

            const date = start.format('YYYY-MM-DD');
            const dateIndex = graph.findIndex(item => item.name === date);
            if (dateIndex !== -1) {
                const userIndex = graph[dateIndex].series
                    .findIndex(item => item.user_id === user_id);
                if (userIndex !== -1) {
                    graph[dateIndex].series[userIndex].value += time;
                } else {
                    graph[dateIndex].series.push({
                        user_id,
                        name,
                        value: time,
                    });
                }
            } else {
                graph.push({
                    name: date,
                    series: [{
                        user_id,
                        name,
                        value: time,
                    }],
                });
            }

            return graph;
        }, []);

        setTimeout(this.updateGraphWidth.bind(this));
    }

    can(action: string): boolean {
        return this.allowedAction.can(action);
    }

    canEdit(owner: UserInfo) {
        const user = this.api.getUser();
        if (+owner.user_id === +user.id) {
            return true;
        }

        return this.can('time-intervals/manager_access');
    }

    onSelectionChanged(date: string, intervals: TimeInterval[]) {
        this.selectedIntervalsByDate[date] = intervals;
        this.selectedIntervals = Object.keys(this.selectedIntervalsByDate)
            .map(date => this.selectedIntervalsByDate[date])
            .reduce((total, current) => total.concat(current), []);
        this.cdr.detectChanges();
    }

    reload() {
        this.screenshotLists.forEach(screenshotList => screenshotList.reload());
    }



    cleanupParams() : string[] {
        return [
            'screenshotLists',
            'graphWrapper',
            'item',
            'users',
            'totalTime',
            'graphSize',
            'graph',
            'colorScheme',
            'selectedIntervalsByDate',
            'selectedIntervals',
            'api',
            'taskService',
            'router',
            'allowService',
            'cdr',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
