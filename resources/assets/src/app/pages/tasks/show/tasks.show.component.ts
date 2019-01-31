import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

import * as moment from 'moment';

import { ApiService } from '../../../api/api.service';
import { TasksService } from '../tasks.service';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

import { ItemsShowComponent } from '../../items.show.component';

import { Task } from '../../../models/task.model';
import { User } from "../../../models/user.model";
import { TimeInterval } from '../../../models/timeinterval.model';

type TaskWithIntervals = Task & { time_intervals?: TimeInterval[] };

interface UserInfo {
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
export class TasksShowComponent extends ItemsShowComponent implements OnInit {
    public item: TaskWithIntervals = new Task();
    public users: UserInfo[] = [];
    public totalTime: number = 0;

    readonly objectKeys = Object.keys;

    constructor(api: ApiService,
        taskService: TasksService,
        router: ActivatedRoute,
        allowService: AllowedActionsService
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

    formatDurationString(time: number) {
        const duration = moment.duration(time);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }

    setItem(result) {
        this.item = result;

        this.users = this.item.time_intervals.reduce((users: UserInfo[], interval) => {
            const user = interval.user;
            const index = users.findIndex(u => u.user.id === user.id);
            const end = moment.utc(interval.end_at);
            const start = moment.utc(interval.start_at);
            const time = end.diff(start);
            const date = start.format('DD-MM-YYYY');
            if (index === -1) {
                users.push({
                    user,
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
    }
}
