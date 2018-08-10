import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { ApiService } from '../../../api/api.service';
import { Router } from "@angular/router";
import { AllowedActionsService } from "../../roles/allowed-actions.service";
import { UsersService } from '../../users/users.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { User } from '../../../models/user.model';
import { TimeInterval } from '../../../models/timeinterval.model';
import * as $ from 'jquery';
import * as moment from 'moment';
import 'moment-timezone';
import 'fullcalendar';
import 'fullcalendar-scheduler';
import { EventObjectInput, View } from 'fullcalendar';
import { Schedule } from 'primeng/schedule';

@Component({
    selector: 'app-statistic-time',
    templateUrl: './statistic.time.component.html',
    styleUrls: ['../../items.component.scss', './statistic.time.component.scss']
})
export class StatisticTimeComponent implements OnInit {
    @ViewChild('timeline') timeline: Schedule;
    @ViewChild('datePicker') datePicker: ElementRef;

    loading: boolean = false;
    timelineInitialized: boolean = false;
    timelineOptions: any;
    events: EventObjectInput[];
    timezone: string;

    constructor(private api: ApiService,
        private userService: UsersService,
        private timeintervalService: TimeIntervalsService,
        private router: Router,
        allowedService: AllowedActionsService) {

    }

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
        if (!this.timelineInitialized) {
            return;
        }

        this.timeline.changeView(value);
    }

    get timelineDate(): moment.Moment {
        if (!this.timelineInitialized) {
            return moment.utc();
        }

        return this.timeline.getDate();
    }

    set timelineDate(value: moment.Moment) {
        if (!this.timelineInitialized) {
            return;
        }

        this.timeline.gotoDate(value);
    }

    fetchEvents(start: moment.Moment, end: moment.Moment): Promise<EventObjectInput[]> {
        // Add +/- 1 day to avoid issues with timezone.
        const params = {
            'start_at': ['>', start.clone().subtract(1, 'day')],
            'end_at': ['<', end.clone().add(1, 'day')],
        };

        return new Promise<EventObjectInput[]>((resolve) => {
            this.timeintervalService.getItems((intervals: TimeInterval[]) => {
                // Combine consecutive intervals into one event.
                const events = intervals.map(interval => {
                    return {
                        id: interval.id,
                        title: '',
                        resourceId: interval.user_id,
                        start: (moment.utc(interval.start_at) as any).tz(this.timezone),
                        end: (moment.utc(interval.end_at) as any).tz(this.timezone),
                    } as EventObjectInput;
                });

                resolve(events);
            }, params);
        });
    }

    refetchEvents() {
        if (!this.timelineInitialized) {
            return;
        }

        this.$timeline.fullCalendar('refetchEvents');
    }

    getLoadedEventsStartedBetween(user_id: number, start: moment.Moment, end: moment.Moment) {
        // Get loaded events of the specified user, started in the selected time range.
        return this.events.filter(event => {
            const isOfCurrentUser = event.resourceId == user_id;

            const eventStart = moment.utc(event.start);
            const isInPeriod = eventStart.diff(start) >= 0 && eventStart.diff(end) < 0;

            return isOfCurrentUser && isInPeriod;
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

    calculateTimeWorkedOn(user_id: number, start: moment.Moment, end: moment.Moment) {
        const events = this.getLoadedEventsStartedBetween(user_id, start, end);
        // Calculate sum of an event time.
        return events.map(event => {
            const start = moment.utc(event.start);
            const end = moment.utc(event.end);
            return end.diff(start);
        }).reduce((sum, value) => sum + value, 0);
    }

    formatDurationString(time: number) {
        const duration = moment.duration(time);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }

    updateIsWorkingNow() {
        const $rows = $('.fc-resource-area tr[data-resource-id]', this.$timeline);
        $rows.each((index, row) => {
            const $row = $(row);
            const userId = $row.data('resource-id');
            const end = moment.utc();
            const start = end.clone().subtract(10, 'minutes');
            const events = this.getLoadedEventsEndedBetween(userId, start, end);
            const $cell = $('td:nth-child(1) .fc-cell-text', $row);

            if (events.length > 0) {
                $cell.addClass('is_working_now');
            } else {
                $cell.removeClass('is_working_now');
            }
        });
    }

    updateTimeWorkedOn() {
        const view = this.$timeline.fullCalendar('getView');
        const viewStart = (moment as any).tz(view.start.format('YYYY-MM-DD'), this.timezone);
        const viewEnd = (moment as any).tz(view.end.format('YYYY-MM-DD'), this.timezone);
        const $rows = $('.fc-resource-area tr[data-resource-id]', this.$timeline);
        $rows.each((index, row) => {
            const $row = $(row);
            const userId = $row.data('resource-id');
            const timeWorked = this.calculateTimeWorkedOn(userId, viewStart, viewEnd);
            const timeWorkedString = this.formatDurationString(timeWorked);
            const $cell = $('td:nth-child(3) .fc-cell-text', $row);
            $cell.text(timeWorkedString);

            if (timeWorked === 0) {
                $row.addClass('not_worked');
            } else {
                $row.removeClass('not_worked');
            }
        });
    }

    ngOnInit() {
        this.events = [];

        const user = this.api.getUser() as User;

        let timezone = localStorage.getItem('statistics-timezone');
        if (timezone === null) {
            timezone = user.timezone !== null ? user.timezone : 'UTC';
        }
        this.timezone = timezone;

        const now = moment.utc().startOf('day');

        const eventSource = {
            events: async (start, end, timezone, callback) => {
                try {
                    setTimeout(() => { this.loading = true; });
                    let events = await this.fetchEvents(start, end);

                    // If showing events in the past or future.
                    const now = moment.utc();
                    if (moment.utc(end).diff(now) < 0 || moment.utc(start).diff(now) > 0) {
                        // Always load current events to show the 'is working now' indicator.
                        events = events.concat(await this.fetchEvents(now.clone().subtract(10, 'minutes'), now));
                    }

                    this.events = events;
                    callback(events);
                } catch (e) {
                    console.error(e);
                    callback([]);
                    setTimeout(() => { this.loading = false; });
                }
            },
        };

        this.timelineOptions = {
            defaultView: this.defaultView,
            now: now,
            timezone: this.timezone,
            firstDay: 1,
            themeSystem: 'bootstrap3',
            eventColor: '#2ab27b',
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
                    slotWidth: 100,
                    slotLabelFormat: 'ddd, MMM DD',
                    buttonText: 'Week',
                },
                timelineMonth: {
                    type: 'timeline',
                    duration: { months: 1 },
                    slotDuration: { days: 1 },
                    slotLabelFormat: 'ddd, MMM DD',
                    slotWidth: 100,
                    buttonText: 'Month',
                },
                timelineRange: {
                    type: 'timeline',
                    slotDuration: { days: 1 },
                    slotLabelFormat: 'ddd, MMM DD',
                    slotWidth: 100,
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
                    labelText: '',
                    text: () => '',
                    width: '40px',
                },
                {
                    labelText: 'Name',
                    field: 'title',
                },
                {
                    labelText: 'Time Worked',
                    text: () => '',
                },
            ],
            resources: (callback) => {
                this.userService.getItems((users: User[]) => {
                    const resources = users.map(user => {
                        return {
                            id: user.id,
                            title: user.full_name,
                        };
                    });
                    callback(resources);
                });
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
                if (view.name !== 'timelineDay') {
                    const $timeline = this.$timeline;
                    const $rows = $('.fc-resource-area tr[data-resource-id]', $timeline);
                    const rows = $.makeArray($rows);

                    const $days = $('.fc-day[data-date]', $timeline);
                    $days.each((index, dayColumnElement) => {
                        const date = $(dayColumnElement).data('date');
                        const dayStart = (moment as any).tz(date, this.timezone);
                        const dayEnd = dayStart.clone().add(1, 'days');
                        const columnWidth = $(dayColumnElement).width() - 4;

                        const html = rows.map(userRowElement => {
                            const userId = $(userRowElement).data('resource-id');

                            // Calculate time worked by this user per this day.
                            const timeWorked = this.calculateTimeWorkedOn(userId, dayStart, dayEnd);
                            const msIn24Hours = 24 * 60 * 60 * 1000;
                            const progress = timeWorked / msIn24Hours;
                            const percent = Math.round(100 * progress);
                            const timeWorkedString = this.formatDurationString(timeWorked);

                            const topOffset = $(userRowElement).position().top;
                            const progressWrapperClass = timeWorked === 0 ? 'progress-wrapper_empty' : '';

                            return `
<div class="progress-wrapper ${progressWrapperClass}" style="top: ${topOffset}px; width: ${columnWidth}px;">
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <p>${timeWorkedString}</p>
</div>`;
                        }).join('');
                        $(dayColumnElement).html(html);
                    });
                }

                this.updateIsWorkingNow();
                this.updateTimeWorkedOn();

                this.timelineInitialized = true;

                setTimeout(() => { this.loading = false; });
            },
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        };
    }

    setView({ view, start, end }: { view: string, start: moment.Moment, end: moment.Moment }) {
        this.viewName = view;
        this.timeline.gotoDate(start);
        this.$timeline.fullCalendar('option', 'visibleRange', {
            start: start,
            end: end.clone().add(1, 'day'),
        });
    }

    exportCSV() {
        const $timeline = this.$timeline;

        const view = $timeline.fullCalendar('getView');
        const viewStart = (moment as any).tz(view.start.format('YYYY-MM-DD'), this.timezone);
        const viewEnd = (moment as any).tz(view.end.format('YYYY-MM-DD'), this.timezone);

        const $rows = $('.fc-resource-area tr[data-resource-id]', $timeline);
        const rows = $.makeArray($rows);

        const $days = $('.fc-day[data-date]', $timeline);
        const days = $.makeArray($days);

        let header = ['"Name"', '"Time Worked"'];
        if (view.name !== 'timelineDay') {
            const daysLabels = days.map(day => {
                const date = $(day).data('date');
                const dateString = (moment as any).tz(date, this.timezone).format('YYYY-MM-DD');
                return `"${dateString}"`;
            });
            header = header.concat(daysLabels);
        }

        const lines = rows.map(row => {
            const userId = $(row).data('resource-id');
            const user = this.$timeline.fullCalendar('getResourceById', userId);

            const timeWorked = this.calculateTimeWorkedOn(userId, viewStart, viewEnd);
            const timeWorkedHours = moment.duration(timeWorked).asHours().toFixed(2);

            let cells = [`"${user.title}"`, `"${timeWorkedHours}"`];
            if (view.name !== 'timelineDay') {
                const daysData = days.map(day => {
                    const date = $(day).data('date');
                    const dayStart = (moment as any).tz(date, this.timezone);
                    const dayEnd = dayStart.clone().add(1, 'days');

                    // Calculate time worked by this user per this day.
                    const timeWorked = this.calculateTimeWorkedOn(userId, dayStart, dayEnd);
                    const timeWorkedHours = moment.duration(timeWorked).asHours().toFixed(2);
                    return `"${timeWorkedHours}"`;
                });
                cells = cells.concat(daysData);
            }

            return cells.join(',');
        });

        const content = 'data:text/csv;charset=utf-8,' + header.join(',') + '\n' + lines.join('\n');
        window.open(encodeURI(content));
    }

    setTimezone(timezone: string) {
        localStorage.setItem('statistics-timezone', timezone);
        this.timezone = timezone;
        this.refetchEvents();
    }
}
