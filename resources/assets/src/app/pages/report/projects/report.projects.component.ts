import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { ApiService } from '../../../api/api.service';
import { Router } from "@angular/router";
import { AllowedActionsService } from "../../roles/allowed-actions.service";
import { UsersService } from '../../users/users.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { ProjectsService } from '../../projects/projects.service';
import { ProjectReportService } from './report.projects.service';
import { TasksService } from '../../tasks/tasks.service';
import { User } from '../../../models/user.model';
import { TimeInterval } from '../../../models/timeinterval.model';
import { Project } from '../../../models/project.model';
import { Task } from '../../../models/task.model';
import * as $ from 'jquery';
import * as moment from 'moment';
import 'moment-timezone';
import 'fullcalendar';
import 'fullcalendar-scheduler';
import { EventObjectInput, View } from 'fullcalendar';
import { Schedule } from 'primeng/schedule';
import { ResourceInput } from 'fullcalendar-scheduler/src/types/input-types';

@Component({
    selector: 'app-report-projects',
    templateUrl: './report.projects.component.html',
    styleUrls: ['../../items.component.scss', './report.projects.component.scss']
})
export class ReportProjectsComponent implements OnInit {
    @ViewChild('timeline') timeline: Schedule;
    @ViewChild('datePicker') datePicker: ElementRef;

    timelineInitialized: boolean = false;
    timelineOptions: any;
    events: EventObjectInput[];
    resources: ResourceInput[];
    timezone: string;
    datePickerDate: string;
    datePickerEndDate: string;
    projectId: number[];
    loading: boolean = false;

    projects: Project[];
    users: User[];
    tasks: Task[];
    timeIntervals: TimeInterval[];

    constructor(
      private api: ApiService,
      private userService: UsersService,
      private timeIntervalService: TimeIntervalsService,
      private projectReportService: ProjectReportService,
      private projectsService: ProjectsService,
      private tasksService: TasksService,
      private router: Router,
      private allowedService: AllowedActionsService
    ) { }

    readonly defaultView = 'timelineDay';
    readonly datePickerFormat = 'YYYY-MM-DD';

    get $timeline(): JQuery<any> {
        return $(this.timeline.el.nativeElement).children();
    }

    get viewName(): string {
        if (!this.timelineInitialized || !this.$timeline.fullCalendar) {
            return this.defaultView;
        }

        return this.$timeline.fullCalendar('getView').name;
    }

    set viewName(value: string) {
        this.timeline.changeView(value);
    }

    get timelineDate(): moment.Moment {
        return this.timeline.getDate();
    }

    set timelineDate(value: moment.Moment) {
        this.timeline.gotoDate(value);
    }

    /**
     * [projectIdChanged description]
     * @param {[type]} projectId [description]
     */
    projectIdChanged(projectId) {
      this.projectId = projectId;
      this.refetchEvents();
    }

    /**
     * [can description]
     * @param  {string}  action [description]
     * @return {boolean}        [description]
     */
    can(action: string ): boolean {
      return this.allowedService.can(action);
    }

    /**
     * [fetchIntervals description]
     * @param  {moment.Moment}               start [description]
     * @param  {moment.Moment}               end   [description]
     * @return {Promise<EventObjectInput[]>}       [description]
     */
    fetchEvents(start: moment.Moment, end: moment.Moment): Promise<EventObjectInput[]> {
      // Add +/- 1 day to avoid issues with timezone.
      const now = moment.utc().startOf('day').subtract('days', 8);

      const params = {
        'start_at': now.clone().subtract(1, 'day').format('YYYY-MM-DD HH:mm:ss'),
        'end_at': now.clone().add(1, 'day').format('YYYY-MM-DD HH:mm:ss'),
        'project_id': this.projectId ? ['=', this.projectId] : null
      };

      return new Promise<EventObjectInput[]>((resolve) => {
        try {
          this.projectReportService.getItems((events: EventObjectInput[]) => {
            resolve(events);
          }, params);//, 'events');
        } catch (e) {
          console.error(e.message);
        }
      });
    }

    ngOnInit() {
      const now = moment.utc().startOf('day').subtract('days', 8);
      const params = {
        'start_at': now.clone().subtract(1, 'day').format('YYYY-MM-DD HH:mm:ss'),
        'end_at': now.clone().add(1, 'day').format('YYYY-MM-DD HH:mm:ss'),
        'project_id': this.projectId ? ['=', this.projectId] : null,
      };
      /*this.projectReportService.getItems(() => {
        debugger;
      }, null);*/
      // Add +/- 1 day to avoid issues with timezone.

      const eventSource = {
        events: async (start, end, timezone, callback) => {
          try {
            setTimeout(() => { this.loading = true; });
            let events = await this.fetchEvents(start, end);

            // If showing events in the past or future.
            // const now = moment.utc();
            // if (moment.utc(end).diff(now) < 0 || moment.utc(start).diff(now) > 0) {
            //     // Always load current events to show the 'is working now' indicator.
            //     events = events.concat(await this.fetchEvents(now.clone().subtract(1, 'day'), now));
            // }

            this.events = events;
            callback(events);
          } catch (e) {
            console.error(e);
            callback([]);
            setTimeout(() => { this.loading = false; });
          }
        }
      };

      this.timelineOptions = {
        defaultView: this.defaultView,
        now: now,
        timezone: this.timezone,
        firstDay: 1,
        themeSystem: 'bootstrap3',
        views: {
            timelineDay: {
                type: 'timeline',
                duration: { days: 1 },
                slotDuration: { hours: 1 },
                buttonText: 'Day',
            },
            timelineWeek: {
                type: 'timeline',
                duration: { weeks: 1 },
                slotDuration: { days: 1 },
                buttonText: 'Week',
            },
            timelineMonth: {
                type: 'timeline',
                duration: { months: 1 },
                slotDuration: { days: 1 },
                buttonText: 'Month',
            },
            timelineRange: {
                type: 'timeline',
                slotDuration: { days: 1 },
                visibleRange: {
                    start: moment.utc(),
                    end: moment.utc().clone().add(1, 'days'),
                },
                buttonText: 'Date range',
            },
        },
        refetchResourcesOnNavigate: false,
        resourceColumns: [
          {
              labelText: 'Projects',
              field: 'title',
          },
          {
              labelText: 'Time Worked',
              text: () => '',
          }
        ],
        resources: (callback) => {
          // Add +/- 1 day to avoid issues with timezone.
          const now = moment.utc().startOf('day').subtract('days', 6);

          const params = {
            'start_at': now.clone().subtract(1, 'day').format('YYYY-MM-DD HH:mm:ss'),
            'end_at': now.clone().add(1, 'day').format('YYYY-MM-DD HH:mm:ss'),
            'project_id': this.projectId ? ['=', this.projectId] : null,
            'type': 'resources'
          };

          this.projectReportService.getItems((resources: ResourceInput[]) => {
            callback(resources);
          }, params);
        },
        displayEventTime: false,
        eventSources: [eventSource],
        eventClick: (event, jsEvent, view: View) => {
          const userId = event.resourceId;
          /** @todo navigate to the user dashboard. */
          this.router.navigateByUrl('dashboard');
        },
        eventRender: (event, el, view: View) => {
          if (view.name !== 'timelineDay') {
              return false;
          }
        },
          eventAfterAllRender: (view: View) => {

            this.updateIsWorkingNow();
            this.updateTimeWorkedOn();

            this.timelineInitialized = true;

            setTimeout(() => { this.loading = false; });
          },
          schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
      };
      
      // this.projectReportService.getItems((intervals: string) => {
      //     }, params);
    }

    updateIsWorkingNow() {
      const $rows = $('.fc-resource-area tr[data-resource-id]', this.$timeline);

      $rows.each((index, row) => {
        const $row = $(row);
        const resourceId = $row.data('resource-id');
        const end = moment.utc();
        const start = end.clone().subtract(10, 'minutes');
        const events = this.getLoadedEventsEndedBetween(resourceId, start, end);
        const $cell = $('td:nth-child(1) .fc-cell-text', $row);

        if (events.length > 0) {
            $cell.addClass('is_working_now');
        } else {
            $cell.removeClass('is_working_now');
        }
      });
    }

    getLoadedEventsEndedBetween(user_id: number, start: moment.Moment, end: moment.Moment) {
      // Get loaded events of the specified user, started in the selected time range.
      return this.events.filter(event => {
        const isOfCurrentUser = event.resourceId == user_id;

        const eventEnd = moment.utc(event.end);
        const isInPeriod = eventEnd.diff(start) >= 0 && eventEnd.diff(end) < 0;

        return isOfCurrentUser && isInPeriod;
      });
    }

    updateTimeWorkedOn() {
      const view = this.$timeline.fullCalendar('getView');
      const viewStart = (moment as any).tz(view.start.format('YYYY-MM-DD'), this.timezone);
      const viewEnd = (moment as any).tz(view.end.format('YYYY-MM-DD'), this.timezone);
      const $rows = $('.fc-resource-area tr[data-resource-id]', this.$timeline);
      $rows.each((index, row) => {
          const $row = $(row);
          const resourceId = $row.data('resource-id');
          const timeWorked = this.calculateTimeWorkedOn(resourceId, viewStart, viewEnd);
          const timeWorkedString = this.formatDurationString(timeWorked);
          const $cell = $('td:nth-child(3) .fc-cell-text', $row);
          $cell.text(timeWorkedString);

          if (timeWorked === 0) {
              $row.addClass('not_worked');
          } else {
              $row.removeClass('not_worked');
          }

          const end = moment.utc();
          const start = end.clone().subtract(1, 'day');
          const lastUserEvents = this.getLoadedEventsEndedBetween(resourceId, start, end);
          if (lastUserEvents.length > 0) {
              const lastUserEvent = lastUserEvents[lastUserEvents.length - 1];
              const eventEnd = moment(lastUserEvent.end);
              const $nameCell = $('td:nth-child(2) .fc-cell-text', $row);
              $nameCell.append('<p class="last-worked">Last worked ' + eventEnd.from(moment.utc()) + '</p>');
          }
      });
    }

    calculateTimeWorkedOn(resoourceId: number, start: moment.Moment, end: moment.Moment) {
        const events = this.getLoadedEventsStartedBetween(resoourceId, start, end);
        // debugger;
        // Calculate sum of an event time.
        return events.map(event => {
            const start = moment.utc(event.start);
            const end = moment.utc(event.end);
            return end.diff(start);
        }).reduce((sum, value) => sum + value, 0);
    }

    getLoadedEventsStartedBetween(resourceId: number, start: moment.Moment, end: moment.Moment) {
        // Get loaded events of the specified user, started in the selected time range.
        // debugger;
        return this.events.filter(event => {
            const isOfCurrentUser = event.resourceId.indexOf(resourceId) !== -1

            const eventStart = moment.utc(event.start);
            const isInPeriod = eventStart.diff(start) >= 0 && eventStart.diff(end) < 0;

            return isOfCurrentUser && isInPeriod;
        });
    }

    formatDurationString(time: number) {
        const duration = moment.duration(time);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }

    datePickerPrev() {
        this.timeline.prev();
        this.datePickerDate = this.timelineDate.format(this.datePickerFormat);
        this.datePickerEndDate = this.timelineDate.clone().add(1, 'day').format(this.datePickerFormat);
    }

    datePickerNext() {
        this.timeline.next();
        this.datePickerDate = this.timelineDate.format(this.datePickerFormat);
        this.datePickerEndDate = this.timelineDate.clone().add(1, 'day').format(this.datePickerFormat);
    }

    datePickerSelect(value: moment.Moment) {
      if (!this.timelineInitialized) {
        return;
      }

      const date = moment.utc(this.datePickerDate);;
      this.timelineDate = date;
      this.datePickerEndDate = date.clone().add(1, 'day').format(this.datePickerFormat);
    }

    datePickerRangeSelect(value: moment.Moment) {
      if (!this.timelineInitialized) {
        return;
      }

      const start = moment.utc(this.datePickerDate);
      let end = moment.utc(this.datePickerEndDate).add(1, 'day');

      if (end.diff(start) <= 0) {
        end = start.clone().add(1, 'day');
      }

      this.timeline.gotoDate(start);
      this.$timeline.fullCalendar('option', 'visibleRange', {
        start: start,
        end: end,
      });
    }

  refetchEvents() {
    // if (!this.timelineInitialized) {
    //   return;
    // }

    this.$timeline.fullCalendar('refetchEvents');
  }  
}
