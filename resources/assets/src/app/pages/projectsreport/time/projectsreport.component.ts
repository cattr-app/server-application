import {Component, OnInit, ViewChild, AfterViewInit} from '@angular/core';
import {NgSelectComponent} from '@ng-select/ng-select';

import {ViewSwitcherComponent} from './view-switcher/view-switcher.component';

import {ApiService} from '../../../api/api.service';
import {UsersService} from '../../users/users.service';
import {ProjectReportService} from './projectsreport.service';

import {User} from '../../../models/user.model';

import * as moment from 'moment';
import 'moment-timezone';

import {Observable} from 'rxjs/Rx';
import 'rxjs/operator/map';
import 'rxjs/operator/share';
import 'rxjs/operator/switchMap';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

interface SelectItem {
  id: number,
  title: string,
}

interface TaskData {
  id: number;
  project_id: number;
  user_id: number;
  task_name: string;
  duration: number;
};

interface UserData {
  id: number;
  full_name: string;
  avatar: string;
  tasks: TaskData[];
  tasks_time: number;
};

interface ProjectData {
  id: number;
  name: string;
  users: UserData[];
  project_time: number;
};

@Component({
  selector: 'app-statistic-time',
  templateUrl: './projectsreport.component.html',
  styleUrls: ['../../items.component.scss', './projectsreport.component.scss']
})
export class ProjectsreportComponent implements OnInit, AfterViewInit {
  @ViewChild('userSelect') userSelect: NgSelectComponent;
  @ViewChild('projectSelect') projectSelect: NgSelectComponent;
  @ViewChild('viewSwitcher') viewSwitcher: ViewSwitcherComponent;

  // Used to show loading indicators.
  loading = true;
  usersLoading = true;
  projectsLoading = true;

  availableUsers: SelectItem[] = [];
  selectedUserIds: number[] = [];

  availableProjects: SelectItem[] = [];
  selectedProjectIds: number[] = [];

  report: ProjectData[] = [];

  constructor(protected api: ApiService,
              protected userService: UsersService,
              protected projectReportService: ProjectReportService,
              protected allowedAction: AllowedActionsService,
  ) {}

  readonly defaultView = 'timelineDay';
  readonly formatDate = 'YYYY-MM-DD';

  ngOnInit() {
    // Fetch available users from the API.
    const availableUsers$ = Observable.from(this.fetchUsers()).share();
    availableUsers$.subscribe(users => {
      /// Add the 'select all' option.
      this.availableUsers = [{ id: -1, title: 'Select all' }, ...users];
      // Select all users initially.
      setTimeout(() => {
        this.selectedUserIds = users.map(user => user.id);
        this.userSelect.changeEvent.emit(users);
      });
      this.usersLoading = false;
    });
  }

  ngAfterViewInit() {
    const selectedUsers$ = (this.userSelect.changeEvent.asObservable() as Observable<SelectItem[]>)
      .map(users => users.filter(user => user.id !== -1)).share();

    const availableProjects$ = selectedUsers$.switchMap(users => {
      const userIds = users.map(user => user.id);
      return Observable.from(this.fetchProjects(userIds));
    });
    availableProjects$.subscribe(projects => {
      /// Add the 'select all' option.
      this.availableProjects = [{ id: -1, title: 'Select all' }, ...projects];
      // Select all users initially.
      setTimeout(() => {
        this.selectedProjectIds = projects.map(project => project.id);
        this.projectSelect.changeEvent.emit(projects);
      });
      this.projectsLoading = false;
    });

    const selectedProjects$ = (this.projectSelect.changeEvent.asObservable() as Observable<SelectItem[]>)
      .map(projects => projects.filter(project => project.id !== -1)).share();

    const view$ = this.viewSwitcher.setView.asObservable().share();

    const report$ = view$.combineLatest(selectedUsers$, selectedProjects$, (view, users, projects) => {
      const start = view.start.format(this.formatDate);
      const end = view.end.format(this.formatDate);
      const userIds = users.map(user => user.id);
      const projectIds = projects.map(project => project.id);
      return { userIds, projectIds, start, end };
    }).switchMap(data => this.fetchReport(data));
    report$.subscribe(data => {
      this.report = data;
    });
  }

  // Fetches available users from the API.
  fetchUsers() {
    return new Promise<SelectItem[]>(resolve => {
      this.userService.getItems((users: User[]) => {
        const userData = users.map(user => {
          return {
            id: user.id,
            title: user.full_name,
          };
        });

        resolve(userData);
      });
    });
  }

  // Fetches available projects of specified users from the API.
  fetchProjects(userIds: number[]) {
    return new Promise<SelectItem[]>(resolve => {
      this.projectReportService.getProjects(userIds).then(projects => {
        const projectsData = (projects as any).map(project => {
          return {
            id: project.id,
            title: project.name,
          };
        });

        resolve(projectsData);
      });
    });
  }

  // Fetches report from the API.
  fetchReport({
    userIds,
    projectIds,
    start,
    end,
  } : {
    userIds: number[],
    projectIds: number[],
    start: string,
    end: string,
  }) {
    const params = {
      uids: userIds,
      pids: projectIds,
      start_at: start,
      end_at: end,
      type: 'report',
    };

    this.loading = true;
    return new Promise<ProjectData[]>(resolve => {
      this.projectReportService.getItems((report: ProjectData[]) => {
        this.loading = false;
        resolve(report);
      }, params);
    });
  }

  get isManager() {
    return this.allowedAction.can('project-report/manager_access');
  }

  formatDurationString(time: number) {
      const duration = moment.duration(time, 'seconds');
      const hours = Math.floor(duration.asHours());
      const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
      return `${hours}h ${minutes}m`;
  }

  // Handles the 'select all' option.
  userSelected(value) {
    if (value.id === -1) {
      setTimeout(() => {
        // Select all users.
        const users = this.availableUsers.filter(user => user.id !== -1);
        this.selectedUserIds = users.map(user => user.id)
        this.userSelect.changeEvent.emit(users);
      });
    }
  }

  // Handles the 'select all' option.
  projectSelected(value) {
    if (value.id === -1) {
      setTimeout(() => {
        // Select all projects.
        const projects = this.availableProjects.filter(project => project.id !== -1);
        this.selectedProjectIds = projects.map(project => project.id);
        this.projectSelect.changeEvent.emit(projects);
      });
    }
  }
}
