import { Component, OnInit } from '@angular/core';
import { TimeUseReportService } from './time-use-report.service';

import * as moment from 'moment';

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

@Component({
  selector: 'app-time-use-report',
  templateUrl: './time-use-report.component.html',
  styleUrls: ['./time-use-report.component.scss']
})
export class TimeUseReportComponent implements OnInit {
  report: Report = { users: [] };

  constructor(
    protected service: TimeUseReportService,
  ) { }

  ngOnInit() {
    const params = {
      'user_ids': [1, 12, 13, 14, 15, 16, 17, 18, 19],
      'start_at': '2006-04-01',
      'end_at': '2018-09-30',
    };

    this.service.getItems((result: Report[]) => {
      this.report = result.length ? result[0] : { users: [] };
    }, params);
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
}
