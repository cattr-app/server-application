import { Component, OnInit, IterableDiffer, IterableDiffers, DoCheck } from '@angular/core';

import {Task} from '../../../models/task.model';

import {DashboardService} from '../dashboard.service';
import {ApiService} from '../../../api/api.service';

import * as moment from 'moment';

@Component({
  selector: 'dashboard-tasklist',
  templateUrl: './tasks.list.component.html'
})
export class TaskListComponent implements OnInit, DoCheck {
    itemsArray: Task[] = [];
    itemsDiffer: IterableDiffer<Task>;
    totalTime = 0;

    get totalTimeStr(): string {
        const duration = moment.duration(this.totalTime, 'seconds');
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        const hoursStr = hours > 9 ? '' + hours : '0' + hours;
        const minutesStr = minutes > 9 ? '' + minutes : '0' + minutes;
        return `${hoursStr}:${minutesStr}`;
    }

    constructor(
        protected api: ApiService,
        protected dashboardService: DashboardService,
        differs: IterableDiffers,
    ) {
        this.itemsDiffer = differs.find(this.itemsArray).create();
    }

    ngOnInit() {
        this.reload();
    }

    ngDoCheck() {
        const itemsChanged = this.itemsDiffer.diff(this.itemsArray);
        if (itemsChanged) {
            this.totalTime = this.itemsArray
                .map(task => moment.duration(task.total_time))
                .reduce((total, duration) => total + duration.asSeconds(), 0);
        }
    }

    setTasks(result) {
        this.itemsArray = result;
    }

    reload() {
        const user: any = this.api.getUser() ? this.api.getUser() : null;
        const params = {
            'user_id': user.id,
            'with': 'project'
        };
        this.dashboardService.getTasks(this.setTasks.bind(this), params);
    }
}
