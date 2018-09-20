import { Component, OnInit, DoCheck, IterableDiffers, IterableDiffer } from '@angular/core';

import * as moment from 'moment';

import { TimeUseReportService } from './time-use-report.service';
import { UsersService } from '../users/users.service';

import { User } from '../../models/user.model';

interface ReportTask {
  task_id: number;
  project_id: number;
  name: string;
  project_name: string;
  total_time: number;
};

interface ReportUser {
  user_id: number;
  name: string;
  avatar?: string;
  tasks: ReportTask[];
  total_time: number;
};

interface Report {
  users: ReportUser[];
};

enum TaskOrder {
  TaskAsc,
  TaskDesc,
  ProjectAsc,
  ProjectDesc,
  TimeAsc,
  TimeDesc,
}

@Component({
  selector: 'app-time-use-report',
  templateUrl: './time-use-report.component.html',
  styleUrls: ['./time-use-report.component.scss']
})
export class TimeUseReportComponent implements OnInit, DoCheck {
  users: number[] = [];
  usersDiffer: IterableDiffer<number> = null;

  start: moment.Moment = moment.utc().startOf('month');
  end: moment.Moment = this.start.clone().add(1, 'month');
  range: string = 'month';

  isLoading: boolean = true;
  report: Report = { users: [] };
  order: TaskOrder = TaskOrder.TimeDesc;

  constructor(
    protected service: TimeUseReportService,
    protected userService: UsersService,
    differs: IterableDiffers,
  ) {
    this.usersDiffer = differs.find(this.users).create();
  }

  fetchReport() {
    this.isLoading = true;

    const params = {
      'user_ids': this.users,
      'start_at': this.start.format('YYYY-MM-DD'),
      'end_at': this.end.format('YYYY-MM-DD'),
    };

    this.service.getItems((result: Report[]) => {
      this.report = result.length ? result[0] : { users: [] };
      this.isLoading = false;
    }, params);
  }

  ngOnInit() {
    this.userService.getItems((users: User[]) => {
      this.users = users.map(user => user.id);
      this.fetchReport();
    });
  }

  ngDoCheck() {
    const usersChanges = this.usersDiffer.diff(this.users);
    if (usersChanges) {
      this.fetchReport();
    }
  }

  formatUserTime(time: number) {
    const duration = moment.duration(time, 'seconds');

    const hours = Math.floor(duration.asHours());
    const minutes = Math.floor(duration.asMinutes()) - 60 * hours;

    return `${hours}h ${minutes}m`;
  }

  formatTaskTime(time: number) {
    const duration = moment.duration(time, 'seconds');

    const hours = Math.floor(duration.asHours());
    const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
    const seconds = Math.floor(duration.asSeconds()) - 3600 * hours - 60 * minutes;

    const minutesStr = minutes > 9 ? minutes : '0' + minutes;
    const secondsStr = seconds > 9 ? seconds : '0' + seconds;

    return `${hours}:${minutesStr}:${secondsStr}`;
  }

  setOrder(column: string) {
    switch (column) {
      case 'task':
        this.order = this.order === TaskOrder.TaskAsc
          ? TaskOrder.TaskDesc : TaskOrder.TaskAsc;
        break;

      case 'project':
        this.order = this.order === TaskOrder.ProjectAsc
          ? TaskOrder.ProjectDesc : TaskOrder.ProjectAsc;
        break;

      default:
      case 'time':
        this.order = this.order === TaskOrder.TimeDesc
          ? TaskOrder.TimeAsc : TaskOrder.TimeDesc;
        break;
    }

    this.report.users.map(user => {
      user.tasks = user.tasks.sort((a, b) => {
        switch (this.order) {
          case TaskOrder.TaskAsc: return a.name.localeCompare(b.name);
          case TaskOrder.TaskDesc: return b.name.localeCompare(a.name);
          case TaskOrder.ProjectAsc: return a.project_name.localeCompare(b.project_name);
          case TaskOrder.ProjectDesc: return b.project_name.localeCompare(a.project_name);
          case TaskOrder.TimeAsc: return a.total_time - b.total_time;
          default:
          case TaskOrder.TimeDesc: return b.total_time - a.total_time;
        }
      });
    });
  }
}
