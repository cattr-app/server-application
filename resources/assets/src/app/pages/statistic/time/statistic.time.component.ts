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

@Component({
    selector: 'app-statistic-time',
    templateUrl: './statistic.time.component.html',
    styleUrls: ['../../items.component.scss']
})
export class StatisticTimeComponent implements OnInit {
    @ViewChild("fileInput") fileInput;
    @ViewChild('calendar') calendar;

    public item: Screenshot = new Screenshot();
    public userList: User;
    public timeintervalList: TimeInterval;

    header: any;
    options: any;

    constructor(api: ApiService,
                private userService: UsersService,
                private timeintervalService: TimeIntervalsService,
                private router: Router,
                allowedService: AllowedActionsService,) {

    }

    ngOnInit() {
        /**
         * @todo uncomment it, when data will be fill
         */
        // this.userService.getItems(this.onUsersGet.bind(this));
        // this.timeintervalService.getItems(this.onTimeIntervalGet.bind(this));
        this.header = {
            left: '',
            center: 'prev title next',
            right: 'timelineDay,timelineWeek,timelineMonth,timelineRange'
        };

        const eventSource = {
            events: (start, end, timezone, callback) => {
                const params = {
                    'start_at': ['>', start],
                    'end_at': ['<', end],
                };
                this.timeintervalService.getItems((intervals: TimeInterval[]) => {
                    // Combine consecutive intervals into one event.
                    const events = intervals.map(interval => {
                        return {
                            title: '',
                            resourceId: interval.user_id,
                            start: interval.start_at,
                            end: interval.end_at,
                        };
                    }).sort((a, b) => {
                        // Sort by start time.
                        const aStart = moment(a.start);
                        const bStart = moment(b.start);
                        return aStart.diff(bStart);
                    }).reduce((arr, curr) => {
                        const count = arr.length;
                        if (count === 0) {
                            return [curr];
                        }

                        // Combine last & current interval if same user and time between less than one second.
                        const last = arr[count - 1];
                        const isSameUser = last.resourceId === curr.resourceId;

                        const lastEnd = moment(last.end);
                        const currStart = moment(curr.start);
                        const isConsecutive = Math.abs(currStart.diff(lastEnd, 'seconds')) <= 1;

                        if (isSameUser && isConsecutive) {
                            arr[count - 1] = {
                                title: '',
                                resourceId: curr.resourceId,
                                start: last.start,
                                end: curr.end,
                            };
                        } else {
                            arr.push(curr);
                        }

                        return arr;
                    }, []);

                    callback(events);
                }, params);
            },
        };

        this.options = {
            defaultView: 'timelineDay',
            now: '2006-04-07', // For debug.
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
            refetchResourcesOnNavigate: true,
            resourceColumns: [
                {
                    labelText: 'Names',
                    field: 'title',
                },
                {
                    labelText: 'Time Worked',
                    text: resource => {
                        const $calendar = $(this.calendar.el.nativeElement).children();
                        const view = $calendar.fullCalendar('getView');
                        // Get events of the current user in the current view.
                        const events = $calendar.fullCalendar('clientEvents', event => {
                            const isOfCurrentUser = event.resourceId == resource.id;

                            const start = moment(event.start);
                            const end = moment(event.end);
                            const viewStart = moment(view.start);
                            const viewEnd = moment(view.end);
                            const isInCurrentView = start.diff(viewStart) >= 0 && end.diff(viewEnd) < 0;

                            return isOfCurrentUser && isInCurrentView;
                        });
                        // Calculate sum of an event time.
                        const time = events.map(event => {
                            const start = moment(event.start);
                            const end = moment(event.end);
                            return end.diff(start);
                        }).reduce((sum, value) => sum + value, 0);
                        // Format the duration string.
                        return time > 0 ? moment.duration(time).humanize() : '-';
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
            eventClick: (event, jsEvent, view) => {
                const userId = event.resourceId;
                /** @todo navigate to the user dashboard. */
                this.router.navigateByUrl('dashboard');
            },
            eventRender: (event, el, view) => {
                if (view.name !== 'timelineDay') {
                    return false;
                }
            },
            dayRender: (date, cell) => {
                const $calendar = $(this.calendar.el.nativeElement).children();
                const view = $calendar.fullCalendar('getView');
                if (view.name !== 'timelineDay') {
/*                     const $rows = $('.fc-resource-area tr[data-resource-id]', $calendar);
                    const rows = $.makeArray($rows);
                    const html = rows.map(row => {
                        const resourceId = $(row).data('resource-id');
                        // Get events of the current user in the current day.
                        const events = $calendar.fullCalendar('clientEvents', event => {
                            const isOfCurrentUser = event.resourceId == resourceId;

                            const start = moment(event.start);
                            const end = moment(event.end);
                            const min = moment(date);
                            const max = min.clone();
                            max.add(1, 'days');

                            console.log(min);
                            console.log(max);

                            const isInCurrentDay = start.diff(min) >= 0 && end.diff(max) < 0;

                            return isOfCurrentUser && isInCurrentDay;
                        });

                        console.log(events);

                        const progress = 0.5;
                        const percent = Math.round(100 * progress);

                        return `
<div class="progress">
    <div class="progress-bar" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
</div>`;
                    }).reduce((sum, curr) => sum + curr, '');
                    $(cell).html(html); */
                }
            },
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        };
    }

    protected onUsersGet(userList: User)
    {
        this.userList = userList;
    }

    protected onTimeIntervalGet(timeintervalList: TimeInterval)
    {
        this.timeintervalList = timeintervalList;
    }
}
