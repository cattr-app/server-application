import {Component, OnInit, OnDestroy, ViewChild, AfterViewInit, ChangeDetectorRef} from '@angular/core';
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
import { UsersService } from '../users/users.service';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, OnDestroy, AfterViewInit {
    @ViewChild('tabs') tabs: TabsetComponent;
    @ViewChild('taskList') taskList: TaskListComponent;
    @ViewChild('tabOwn', {read: TabDirective}) tabOwn: TabDirective;
    @ViewChild('tabTimeline', {read: TabDirective}) tabTimeline: TabDirective;
    @ViewChild('tabTeam', {read: TabDirective}) tabTeam: TabDirective;
    @ViewChild('screenshotList') screenshotList: ScreenshotListComponent;
    @ViewChild('userSelect') userSelect: UserSelectorComponent;
    @ViewChild('userStatistic') userStatistic: StatisticTimeComponent;
    @ViewChild('teamStatistic') teamStatistic: StatisticTimeComponent;

    userIsManager: boolean = false;
    selectedTab: TabDirective = null;
    selectedIntervals: TimeInterval[] = [];
    taskFilter: string|Project|Task = '';
    canManageIntervals: boolean = false;
    currentUser: User = null;
    selectedUsers: User[] = [];

    constructor(
        protected api: ApiService,
        protected allowedAction: AllowedActionsService,
        protected cdr: ChangeDetectorRef,
        protected userService: UsersService,
    ) { }

    ngOnInit() {
        const user = this.api.getUser();
        this.userService.getItem(user.id, user => {
            this.currentUser = user;
        });

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
        if (tab) {
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

        if (this.teamStatistic) {
            this.teamStatistic.update();
        }

        this.filter('');
    }

    filter(filter: string|Task|Project) {
        this.taskFilter = filter;
        this.taskList.filter(filter);
        this.screenshotList.filter(filter);

        if (this.teamStatistic) {
            this.teamStatistic.filter(filter);
        }
    }

    userFilter(user: User) {
        return !!user.active;
    }


    cleanupParams() : string[] {
        return [
            'api',
            'allowedAction',
            'cdr',
            'userService',
            'tabs',
            'taskList',
            'tabOwn',
            'tabTeam',
            'screenshotList',
            'userSelect',
            'userStatistic',
            'teamStatistic',
            'userIsManager',
            'selectedTab',
            'selectedIntervals',
            'taskFilter',
            'currentUser',
            'selectedUsers',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
