import {Component, OnInit, ViewChild, AfterViewInit} from '@angular/core';
import { TabsetComponent, TabDirective } from 'ngx-bootstrap';

import { ApiService } from '../../api/api.service';
import { AllowedActionsService } from '../roles/allowed-actions.service';

import { TimeInterval } from '../../models/timeinterval.model';
import { Project } from '../../models/project.model';
import { Task } from '../../models/task.model';

import { TaskListComponent } from './tasklist/tasks.list.component';
import { ScreenshotListComponent } from './screenshotlist/screenshot.list.component';
import { StatisticTimeComponent } from '../statistic/time/statistic.time.component';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, AfterViewInit {
    @ViewChild('tabs') tabs: TabsetComponent;
    @ViewChild('taskList') taskList: TaskListComponent;
    @ViewChild('screenshotList') screenshotList: ScreenshotListComponent;
    @ViewChild('statistic') statistic: StatisticTimeComponent;

    userIsManager: boolean = false;
    selectedTab: string = '';
    selectedIntervals: TimeInterval[] = [];
    taskFilter: string|Project|Task = '';
    canManageIntervals: boolean = false;

    constructor(
        protected api: ApiService,
        protected allowedAction: AllowedActionsService,
    ) { }

    ngOnInit() {
        this.allowedUpdated();
        let allowedCallback = this.allowedUpdated.bind(this);
        this.allowedAction.subscribeOnUpdate(allowedCallback);
    }

    allowedUpdated() {
        this.userIsManager = this.allowedAction.can('dashboard/manager_access');
        this.canManageIntervals = this.allowedAction.can('time-intervals/manager_access');
    }

    ngAfterViewInit() {
        if (this.userIsManager) {
            const tabHeading = localStorage.getItem('dashboard-tab');
            if (tabHeading !== null) {
                const index = this.tabs.tabs.findIndex(tab => tab.heading === tabHeading);
                if (index !== -1) {
                    setTimeout(() => {
                        if (typeof this.tabs !== 'undefined') {
                            this.selectedTab = tabHeading;
                            this.tabs.tabs[index].active = true;
                        }
                    });
                }
            }
        }
    }

    changeTab(tab: TabDirective) {
        if (tab.heading !== undefined) {
            this.selectedTab = tab.heading;
            this.selectedIntervals = [];
            this.reload();

            localStorage.setItem('dashboard-tab', this.selectedTab);
        }
    }

    changeSelection(intervals: TimeInterval[]) {
        setTimeout(() => {
            this.selectedIntervals = intervals;
        });
    }

    reload() {
        this.taskList.reload();
        this.screenshotList.reload();

        if (this.statistic) {
            this.statistic.reload();
        }

        this.filter('');
    }

    filter(filter: string|Task|Project) {
        this.taskFilter = filter;
        this.taskList.filter(filter);
        this.screenshotList.filter(filter);

        if (this.statistic) {
            this.statistic.filter(filter);
        }
    }
}
