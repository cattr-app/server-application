import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Router} from "@angular/router";
import {Screenshot} from "../../../models/screenshot.model";
import {AllowedActionsService} from "../../roles/allowed-actions.service";
import {UsersService} from '../../users/users.service';
import {TimeIntervalsService} from '../../timeintervals/timeintervals.service';
import {User} from '../../../models/user.model';
import {TimeInterval} from '../../../models/timeinterval.model';
import * as $ from 'jquery';
import * as moment from 'moment';
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
    @ViewChild("fileInput") fileInput;
    @ViewChild('calendar') calendar: Schedule;

    public item: Screenshot = new Screenshot();
    public userList: User;
    public timeintervalList: TimeInterval;

    header: any;
    options: any;
    events: EventObjectInput[];

    constructor(api: ApiService,
                private userService: UsersService,
                private timeintervalService: TimeIntervalsService,
                private router: Router,
                allowedService: AllowedActionsService) {

    }

    get $calendar(): JQuery<any> {
        return $(this.calendar.el.nativeElement).children();
    }

    fetchEvents(start: moment.Moment, end: moment.Moment): Promise<EventObjectInput[]> {
        const params = {
            'start_at': ['>', start],
            'end_at': ['<', end],
        };

        return new Promise<EventObjectInput[]>((resolve) => {
            this.timeintervalService.getItems((intervals: TimeInterval[]) => {
                // Combine consecutive intervals into one event.
                const events = intervals.map(interval => {
                    return {
                        id: interval.id,
                        title: '',
                        resourceId: interval.user_id,
                        start: interval.start_at,
                        end: interval.end_at,
                    } as EventObjectInput;
                }).sort((a, b) => {
                    // Sort by user.
                    if (a.resourceId !== b.resourceId) {
                        return a.resourceId - b.resourceId;
                    }
                    // Then sort by start time.
                    const aStart = moment.utc(a.start);
                    const bStart = moment.utc(b.start);
                    return aStart.diff(bStart);
                }).reduce((arr, curr) => {
                    const count = arr.length;
                    if (count === 0) {
                        return [curr];
                    }

                    // Combine last & current interval if same user and time between less than one second.
                    const last = arr[count - 1];
                    const isSameUser = last.resourceId === curr.resourceId;

                    const lastEnd = moment.utc(last.end);
                    const currStart = moment.utc(curr.start);
                    const isConsecutive = Math.abs(currStart.diff(lastEnd, 'seconds')) <= 1;

                    if (isSameUser && isConsecutive) {
                        arr[count - 1] = {
                            id: last.id,
                            title: '',
                            resourceId: curr.resourceId,
                            start: last.start,
                            end: curr.end,
                        };
                    } else {
                        arr.push(curr);
                    }

                    return arr;
                }, [] as EventObjectInput[]);

                resolve(events);
            }, params);
        });
    }

    ngOnInit() {
        /**
         * @todo uncomment it, when data will be fill
         */
        // this.userService.getItems(this.onUsersGet.bind(this));
        // this.timeintervalService.getItems(this.onTimeIntervalGet.bind(this));
        this.header = {
            left: false,
            center: false,
            right: 'timelineDay,timelineWeek,timelineMonth,timelineRange'
        };

        const eventSource = {
            events: async (start, end, timezone, callback) => {
                try {
                    const events = await this.fetchEvents(start, end);
                    this.events = events;
                    callback(events);
                } catch (e) {
                    console.error(e);
                    callback([]);
                }
            },
        };

        this.options = {
            defaultView: 'timelineDay',
            now: '2006-04-07', // For debug.
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
                    duration: { months: 1 },
                    slotDuration: { days: 1 },
                    buttonText: 'Date range',
                },
            },
            viewRender: (view: View, el) => {
                const $prev = $(`
<button type="button" class="fc-prev-button btn btn-default" aria-label="prev">
    <span class="glyphicon glyphicon-chevron-left"></span>
</button>`);
                $prev.click(e => this.calendar.prev());
                const $prevWrapper = $('<span class="input-group-btn">').append($prev);

                const date = view.start;
                const dateStr = date.format('YYYY-MM-DD');
                const dateAttributes = view.name === 'timelineDay' ? '' : 'readonly';
                const $date = $(`<input type="date" class="form-control" value="${dateStr}" ${dateAttributes} />`);
                $date.change(e => this.calendar.gotoDate(moment($date.val(), 'YYYY-MM-DD')));

                const $next = $(`
<button type="button" class="fc-next-button btn btn-default" aria-label="next">
    <span class="glyphicon glyphicon-chevron-right"></span>
</button>`);
                $next.click(e => this.calendar.next());
                const $nextWrapper = $('<span class="input-group-btn">').append($next);

                const $dateGroup = $('<div class="input-group">');
                $dateGroup.append($prevWrapper);
                $dateGroup.append($date);
                $dateGroup.append($nextWrapper);

                const $center = $('.fc-center', this.$calendar);
                $center.addClass('form-inline');
                $center.empty();
                $center.append($dateGroup);
            },
            refetchResourcesOnNavigate: true,
            resourceColumns: [
                {
                    labelText: 'Names',
                    field: 'title',
                },
                {
                    labelText: 'Time Worked',
                    text: resource => {
                        const view = this.$calendar.fullCalendar('getView');
                        const viewStart = moment.utc(view.start);
                        const viewEnd = moment.utc(view.end);
                        // Get events of the current user in the current view.
                        const events = this.events.filter(event => {
                            const isOfCurrentUser = event.resourceId == resource.id;

                            const start = moment.utc(event.start);
                            const isInCurrentView = start.diff(viewStart) >= 0 && start.diff(viewEnd) < 0;

                            return isOfCurrentUser && isInCurrentView;
                        });
                        // Calculate sum of an event time.
                        const time = events.map(event => {
                            const start = moment.utc(event.start);
                            const end = moment.utc(event.end);
                            return end.diff(start);
                        }).reduce((sum, value) => sum + value, 0);
                        // Format the duration string.
                        const duration = moment.duration(time);
                        const hours = Math.floor(duration.asHours());
                        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
                        return `${hours}h ${minutes}m`;
                    },
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
                    const $calendar = this.$calendar;
                    const $rows = $('.fc-resource-area tr[data-resource-id]', $calendar);
                    const rows = $.makeArray($rows);

                    const $days = $('.fc-day[data-date]', $calendar);
                    $days.each((index, el) => {
                        const html = rows.map(row => {
                            const resourceId = $(row).data('resource-id');
                            const date = $(el).data('date');
                            const dayStart = moment.utc(date);
                            const dayEnd = dayStart.clone();
                            dayEnd.add(1, 'days');

                            // Get events of the current user in the current day.
                            const events = this.events.filter(event => {
                                const isOfCurrentUser = event.resourceId == resourceId;

                                const start = moment.utc(event.start);
                                const isInCurrentDay = start.diff(dayStart) >= 0 && start.diff(dayEnd) < 0;

                                return isOfCurrentUser && isInCurrentDay;
                            });
                            // Calculate time worked per current day.
                            const timeWorked = events.reduce((acc, event) => {
                                const start = moment.utc(event.start);
                                const end = moment.utc(event.end);
                                return acc + end.diff(start, 'seconds');
                            }, 0);
                            const secondsIn24Hours = 24 * 60 * 60;
                            const progress = timeWorked / secondsIn24Hours;
                            const percent = Math.round(100 * progress);
                            // Format the duration string.
                            const duration = moment.duration(timeWorked, 'seconds');
                            const hours = Math.floor(duration.asHours());
                            const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
                            const timeStr = `${hours}h ${minutes}m`;

                            // Vertical offset from table top.
                            const topOffset = $(row).position().top;
                            const width = $(el).width();
                            const progressWrapperClass = percent === 0 ? 'progress-wrapper_empty' : '';

                            return `
<div class="progress-wrapper ${progressWrapperClass}" style="top: ${topOffset}px; width: ${width}px;">
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <p>${timeStr}</p>
</div>`;
                        }).reduce((sum, curr) => sum + curr, '');
                        $(el).html(html);
                    });
                }
            },
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        };
    }

    protected onUsersGet(userList: User) {
        this.userList = userList;
    }

    protected onTimeIntervalGet(timeintervalList: TimeInterval) {
        this.timeintervalList = timeintervalList;
    }
}
