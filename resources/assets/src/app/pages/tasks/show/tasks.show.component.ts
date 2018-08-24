import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';

import * as moment from 'moment';

import {ApiService} from '../../../api/api.service';
import {TasksService} from '../tasks.service';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

import {ItemsShowComponent} from '../../items.show.component';

import {Task} from '../../../models/task.model';
import {User} from "../../../models/user.model";
import { TimeInterval } from '../../../models/timeinterval.model';

type TaskWithIntervals = Task & { time_intervals?: TimeInterval[] };

interface UserInfo {
    user: User;
    time: number;
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
            if (index === -1) {
                users = users.concat([{
                    user,
                    time: 0,
                }]);
            } else {
                const time = moment.utc(interval.end_at).diff(moment.utc(interval.start_at));
                users[index].time += time;
            }
            return users;
        }, []);

        this.totalTime = this.users.reduce((sum, userInfo) => {
            return sum + userInfo.time;
        }, 0);
    }
}
