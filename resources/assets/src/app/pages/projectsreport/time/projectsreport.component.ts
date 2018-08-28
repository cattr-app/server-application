import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {NgSelectComponent} from '@ng-select/ng-select';
import {ViewData, ViewSwitcherComponent} from './view-switcher/view-switcher.component';
import {PopoverDirective} from 'ngx-bootstrap';

import {ApiService} from '../../../api/api.service';
import {UsersService} from '../../users/users.service';
import {TimeIntervalsService} from '../../timeintervals/timeintervals.service';
import {TasksService} from '../../tasks/tasks.service';
import {ProjectsService} from '../../projects/projects.service';
import {ScreenshotsService} from '../../screenshots/screenshots.service';

import {User} from '../../../models/user.model';
import {Task} from '../../../models/task.model';
import {Project} from '../../../models/project.model';
import {Screenshot} from '../../../models/screenshot.model';

import * as $ from 'jquery';
import * as moment from 'moment';
import 'moment-timezone';

import 'fullcalendar';
import 'fullcalendar-scheduler';
import {EventObjectInput, View} from 'fullcalendar';
import {Schedule} from 'primeng/schedule';
import {ResourceInput} from 'fullcalendar-scheduler/src/exports';

import {Observable} from 'rxjs/Rx';
import 'rxjs/operator/map';
import 'rxjs/operator/share';
import 'rxjs/operator/switchMap';
import {ProjectReportService} from './projectsreport.service';

enum UsersSort {
  NameAsc,
  NameDesc,
  TimeWorkedAsc,
  TimeWorkedDesc
}

interface TimeWorked {
  id: string;
  total: number;
  perDay: { [date: string]: number };
}

@Component({
  selector: 'app-statistic-time',
  templateUrl: './projectsreport.component.html',
  styleUrls: ['../../items.component.scss', './projectsreport.component.scss']
})
export class ProjectsreportComponent implements OnInit {
  @ViewChild('timeline') timeline: Schedule;
  @ViewChild('datePicker') datePicker: ElementRef;
  @ViewChild('userSelect') userSelect: NgSelectComponent;
  @ViewChild('projectSelect') projectSelect: NgSelectComponent;
  @ViewChild('viewSwitcher') viewSwitcher: ViewSwitcherComponent;
  @ViewChild('clickPopover') clickPopover: PopoverDirective;
  @ViewChild('hoverPopover') hoverPopover: PopoverDirective;

  start_at: string = null;
  end_at: string = null;
  selectedUserIds: number[] = [];
  userSelectItems: {}[] = [];

  report: Object[] = [];

  isManager = false;

  projects: Project[] = [];
  projectSelectItems: {}[] = [];
  selectedProjectIds: number[] = [];

  loading = true;
  usersLoading = true;
  projectsLoading = true;
  popoverLoading = true;
  clickPopoverProject: Project = null;
  clickPopoverTask: Task = null;
  clickPopoverScreenshot: Screenshot = null;
  hoverPopoverProject: Project = null;
  hoverPopoverTask: Task = null;
  hoverPopoverEvent: EventObjectInput = null;
  hoverPopoverTime = 0;
  timelineInitialized = false;
  timelineOptions: any;

  view: ViewData;
  viewEvents: EventObjectInput[] = [];
  viewEventsTasks: Task[] = [];
  viewEventsProjects: Project[] = [];
  viewTimeWorked: TimeWorked[] = [];
  latestEvents: EventObjectInput[] = [];
  latestEventsTasks: Task[] = [];
  latestEventsProjects: Project[] = [];
  users: User[] = [];
  selectedUsers: ResourceInput[] = [];
  sortUsers: UsersSort = UsersSort.NameAsc;

  view$: Observable<ViewData>;
  viewEvents$: Observable<EventObjectInput[]>;
  viewEventsTasks$: Observable<Task[]>;
  viewEventsProjects$: Observable<Project[]>;
  viewTimeWorked$: Observable<TimeWorked[]>;
  latestEvents$: Observable<EventObjectInput[]>;
  latestEventsTasks$: Observable<Task[]>;
  latestEventsProjects$: Observable<Project[]>;
  users$: Observable<User[]>;
  selectedUsers$: Observable<User[]>;
  projects$: Observable<Project[]>;
  selectedProjects$: Observable<Project[]>;

  constructor(private api: ApiService,
              private userService: UsersService,
              private timeintervalService: TimeIntervalsService,
              private taskService: TasksService,
              private projectService: ProjectsService,
              private screenshotService: ScreenshotsService,
              private projectReportService: ProjectReportService) {
  }

  readonly defaultView = 'timelineDay';
  readonly formatDate = 'YYYY-MM-DD';

  fetchAttachedUsers() {
    const uid = this.api.getUser().id;
    return new Promise<User[]>(resolve => {
      this.userService.getItem(uid, (users: User[]) => {
        users = users.attached_users.map(user => {
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
        this.projects = projects.map(project => {
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
    ///// delete
    this.start_at = '1990-01-01';
    this.end_at = '2990-01-01';
    /////
    const params = {
      uids: this.selectedUserIds,
      pids: this.selectedProjectIds,
      start_at: this.start_at || '',
      end_at: this.end_at || '',
    };
    this.setLoading(true);
    this.projectReportService.getItems(report => {
      this.report = report;
      console.log(this.report);
      this.setLoading(false);
    }, params);
  }
}
