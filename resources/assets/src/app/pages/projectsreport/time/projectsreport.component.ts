import {Component, OnInit, ViewChild} from '@angular/core';
import {NgSelectComponent} from '@ng-select/ng-select';
import {ViewData, ViewSwitcherComponent} from './view-switcher/view-switcher.component';

import {ApiService} from '../../../api/api.service';
import {UsersService} from '../../users/users.service';
import {ProjectReportService} from './projectsreport.service';

import {User} from '../../../models/user.model';
import {Project} from '../../../models/project.model';

import * as moment from 'moment';
import 'moment-timezone';

import {Observable} from 'rxjs/Rx';
import 'rxjs/operator/map';
import 'rxjs/operator/share';
import 'rxjs/operator/switchMap';

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

interface ProjectUsers {
  [key: number]: UserData;
}

interface ProjectData {
  id: number;
  name: string;
  users: ProjectUsers;
  project_time: number;
};

@Component({
  selector: 'app-statistic-time',
  templateUrl: './projectsreport.component.html',
  styleUrls: ['../../items.component.scss', './projectsreport.component.scss']
})
export class ProjectsreportComponent implements OnInit {
  @ViewChild('userSelect') userSelect: NgSelectComponent;
  @ViewChild('projectSelect') projectSelect: NgSelectComponent;
  @ViewChild('viewSwitcher') viewSwitcher: ViewSwitcherComponent;

  start_at: string = null;
  end_at: string = null;
  selectedUserIds: number[] = [];
  userSelectItems: {}[] = [];

  report: ProjectData[] = [];

  isManager = false;

  projects: Project[] = [];
  projectSelectItems: {}[] = [];
  selectedProjectIds: number[] = [];

  loading = true;
  usersLoading = true;
  projectsLoading = true;

  view: ViewData;
  users: User[] = [];

  view$: Observable<ViewData>;
  users$: Observable<User[]>;

  constructor(private api: ApiService,
              private userService: UsersService,
              private projectReportService: ProjectReportService) {
  }

  readonly defaultView = 'timelineDay';
  readonly formatDate = 'YYYY-MM-DD';

  values = (Object as any).values;

  fetchAttachedUsers() {
    const uid = this.api.getUser().id;
    return new Promise<User[]>(resolve => {
      this.userService.getItem(uid, (users: User[]) => {
        users = (users as any).attached_users.map(user => {
          return {
            id: user.id,
            title: user.full_name,
          };
        });
        resolve(users);
      });
    });
  }

  userIsManager() {
    return this.api.getUser().role_id === 5;
  }

  ngOnInit() {
    this.view = {
      name: this.defaultView,
      start: moment.utc().startOf('day'),
      end: moment.utc().startOf('day').add(1, 'day'),
      timezone: 'UTC',
    };
    this.viewSwitcher.onChange = this.loadReport;

    this.users$ = Observable.from(this.fetchAttachedUsers()).share();
    this.users$.subscribe(users => {
      this.users = users;
      this.userSelectItems = [{id: -1, title: 'Select all'}, ...users];
      this.selectedUserIds = users.map(user => user.id);
      this.usersLoading = false;
      this.loadProjects().then(() => this.loadReport());
    });

    this.view$ = this.viewSwitcher.setView.asObservable();
    this.view$.filter(view => {
      return view.start.unix() !== this.view.start.unix()
        || view.end.unix() !== this.view.end.unix()
        || view.name !== this.view.name
        || view.timezone !== this.view.timezone;
    }).subscribe(() => {
      setTimeout(() => {
        if (this.viewSwitcher.dateSelector) {
          const date = this.viewSwitcher.dateSelector._inputDate;
          const dates = date.match(/^(\d{4}-\d\d-\d\d)( - (\d{4}-\d\d-\d\d))?$/);
          if (dates) {
            this.start_at = dates[1];
            if (dates.length > 1) {
              this.end_at = dates[3];
            }
          } else {
            this.start_at = moment(this.viewSwitcher.dateSelector._inputDate).subtract(1, 'month').format(this.formatDate);
            this.end_at = moment(this.viewSwitcher.dateSelector._inputDate).format(this.formatDate);
          }
        } else {
          this.start_at = moment(this.viewSwitcher.dateRangeSelector.startDate).format(this.formatDate);
          this.end_at = moment(this.viewSwitcher.dateRangeSelector.endDate).format(this.formatDate);
        }
        this.loadReport();
      });
    });
  }

  userSelected(value) {
    if (value.id === -1) {
      setTimeout(() => {
        this.selectedUserIds = this.users.map(user => user.id);
        this.userSelect.changeEvent.emit(this.users);
      });
    }
  }

  projectSelected(value) {
    if (value.id === -1) {
      setTimeout(() => {
        this.selectedProjectIds = this.projects.map(project => project.id);
        this.projectSelect.changeEvent.emit(this.projects);
      });
    }
  }

  setLoading(loading: boolean = true) {
    this.loading = loading;
  }

  loadProjects() {
    return new Promise(resolve => {
      if (this.selectedUserIds.length === 0) {
        this.projects = [];
        this.projectSelectItems = [];
        this.selectedProjectIds = [];
        return;
      }
      this.projectReportService.getProjects(this.selectedUserIds).then(projects => {
        this.projects = (projects as any).map(project => {
          return {
            id: project.id,
            title: project.name,
          };
        });
        this.projectSelectItems = [{id: -1, title: 'Select all'}, ...this.projects];
        this.selectedProjectIds = this.projects.map(user => user.id);
        this.projectsLoading = false;
      }).then(() => {
        resolve(this.projects);
      });
    });
  }

  loadReport() {
    if (this.selectedProjectIds.indexOf(-1) !== -1) {
      return;
    }

    const params = {
      uids: this.selectedUserIds,
      pids: this.selectedProjectIds,
      start_at: this.start_at || '',
      end_at: this.end_at || '',
      type: 'report',
    };

    this.setLoading(true);
    this.projectReportService.getItems((report: ProjectData[]) => {
      this.report = report;
      this.setLoading(false);
    }, params);
  }

  formatDurationString(time: number) {
      const duration = moment.duration(time, 'seconds');
      const hours = Math.floor(duration.asHours());
      const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
      return `${hours}h ${minutes}m`;
  }
}
