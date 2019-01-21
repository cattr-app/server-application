import {Component, OnInit, ViewChild, AfterViewInit, ChangeDetectorRef} from '@angular/core';
import { TabsetComponent, TabDirective } from 'ngx-bootstrap';

import { ApiService } from '../../api/api.service';
import { AllowedActionsService } from '../roles/allowed-actions.service';

import { TimeInterval } from '../../models/timeinterval.model';
import { Project } from '../../models/project.model';
import { Task } from '../../models/task.model';
import { User } from '../../models/user.model';

import { TaskListComponent } from './tasklist/tasks.list.component';
import { ScreenshotListComponent } from './screenshotlist/screenshot.list.component';
import { UserSelectorComponent } from '../../user-selector/user-selector.component';
import { StatisticTimeComponent } from '../statistic/time/statistic.time.component';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, AfterViewInit {
    @ViewChild('tabs') tabs: TabsetComponent;
    @ViewChild('taskList') taskList: TaskListComponent;
    @ViewChild('tabOwn', {read: TabDirective}) tabOwn: TabDirective;
    @ViewChild('tabTeam', {read: TabDirective}) tabTeam: TabDirective;
    @ViewChild('screenshotList') screenshotList: ScreenshotListComponent;
    @ViewChild('userSelect') userSelect: UserSelectorComponent;
    @ViewChild('userStatistic') userStatistic: StatisticTimeComponent;
    @ViewChild('statistic') statistic: StatisticTimeComponent;

    userIsManager: boolean = false;
    selectedTab: TabDirective = null;
    selectedIntervals: TimeInterval[] = [];
    taskFilter: string|Project|Task = '';
    canManageIntervals: boolean = false;
    currentUser: User = null;
    selectedUsers: User[] = [];

    get isOnTeamTab() {
        return this.selectedTab === this.tabTeam;
    }

    get user() {
        return [this.currentUser];
    }

    constructor(
        protected api: ApiService,
        protected allowedAction: AllowedActionsService,
        protected cdr: ChangeDetectorRef,
    ) { }

    ngOnInit() {
        this.currentUser = this.api.getUser();
        this.allowedUpdated();
        let allowedCallback = this.allowedUpdated.bind(this);
        this.allowedAction.subscribeOnUpdate(allowedCallback);
    }

    allowedUpdated() {
        this.userIsManager = this.allowedAction.can('dashboard/manager_access');
        this.canManageIntervals = this.allowedAction.can('time-intervals/manager_access');

        setTimeout(() => {
            if (this.userIsManager && this.tabTeam) {
                this.tabTeam.active = true;
            }
        });
    }

    ngAfterViewInit() {
        if (this.userIsManager && this.tabTeam) {
            this.tabTeam.active = true;
        }

        this.cdr.detectChanges();
    }

    changeTab(tab: TabDirective) {
        if (tab.heading !== undefined) {
            this.selectedTab = tab;
            this.selectedIntervals = [];
            this.reload();
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

    userFilter(user: User) {
        return !!user.active;
    }
}
