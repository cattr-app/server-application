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
import { ResourceInput } from 'fullcalendar-scheduler/src/exports';
import { NgSelectComponent } from '@ng-select/ng-select';
import { Observable } from 'rxjs/Rx';
import 'rxjs/operator/map';
import 'rxjs/operator/share';
import 'rxjs/operator/switchMap';
import { ViewSwitcherComponent, ViewData } from './view-switcher/view-switcher.component';

enum UsersSort {
    NameAsc,
    NameDesc,
    TimeWorkedAsc,
    TimeWorkedDesc
}

interface TimeWorked {
    id: string,
    total: number,
    perDay: { [date: string]: number },
}

@Component({
    selector: 'app-statistic-time',
    templateUrl: './statistic.time.component.html',
    styleUrls: ['../../items.component.scss', './statistic.time.component.scss']
})
export class StatisticTimeComponent implements OnInit {
    @ViewChild('timeline') timeline: Schedule;
    @ViewChild('datePicker') datePicker: ElementRef;
    @ViewChild('userSelect') userSelect: NgSelectComponent;
    @ViewChild('viewSwitcher') viewSwitcher: ViewSwitcherComponent;

    selectedUserIds: string[];

    loading: boolean = true;
    usersLoading: boolean = true;
    timelineInitialized: boolean = false;
    timelineOptions: any;

    view: ViewData;
    viewEvents: EventObjectInput[] = [];
    viewTimeWorked: TimeWorked[] = [];
    latestEvents: EventObjectInput[] = [];
    users: ResourceInput[] = [];
    selectedUsers: ResourceInput[] = [];
    sortUsers: UsersSort = UsersSort.NameAsc;

    view$: Observable<ViewData>;
    viewEvents$: Observable<EventObjectInput[]>;
    viewTimeWorked$: Observable<TimeWorked[]>;
    latestEvents$: Observable<EventObjectInput[]>;
    users$: Observable<ResourceInput[]>;
    selectedUsers$: Observable<ResourceInput[]>;
    sortUsers$: Observable<UsersSort>;
    sortedUsers$: Observable<ResourceInput[]>;

    constructor(private api: ApiService,
        private userService: UsersService,
        private timeintervalService: TimeIntervalsService,
        private router: Router) {
    }

    readonly defaultView = 'timelineDay';
    readonly datePickerFormat = 'YYYY-MM-DD';

    get $timeline(): JQuery<any> {
        return $(this.timeline.el.nativeElement).children();
    }

    get timezoneOffset(): number {
        return -(moment as any).tz.zone(this.view.timezone).utcOffset(this.view.start);
    }

    fetchEvents(start: moment.Moment, end: moment.Moment): Promise<EventObjectInput[]> {
        const params = {
            'start_at': ['>', start],
            'end_at': ['<', end],
        };

        return new Promise<EventObjectInput[]>((resolve) => {
            this.timeintervalService.getItems((intervals: TimeInterval[]) => {
                const events = intervals.map(interval => {
                    return {
                        id: interval.id,
                        title: '',
                        resourceId: interval.user_id,
                        start: moment.utc(interval.start_at).add(this.timezoneOffset, 'minutes'),
                        end: moment.utc(interval.end_at).add(this.timezoneOffset, 'minutes'),
                    } as EventObjectInput;
                });

                resolve(events);
            }, params);
        });
    }

    fetchResources() {
        return new Promise<ResourceInput[]>(resolve => {
            this.userService.getItems((users: User[]) => {
                const resources = users.map(user => {
                    return {
                        id: user.id.toString(),
                        title: user.full_name,
                    };
                });
                resolve(resources);
            });
        });
    }

    ngOnInit() {
        this.view = {
            name: this.defaultView,
            start: moment.utc().startOf('day'),
            end: moment.utc().startOf('day').add(1, 'day'),
            timezone: 'UTC',
        };

        this.users$ = Observable.from(this.fetchResources()).share();
        this.users$.subscribe(users => {
            this.users = users;
            this.selectedUserIds = users.map(user => user.id);
            this.usersLoading = false;
        });

        const selectUser$ = this.userSelect.changeEvent.asObservable() as Observable<ResourceInput[]>;
        this.selectedUsers$ = this.users$.concat(selectUser$).share();

        this.view$ = this.viewSwitcher.setView.asObservable();
        this.viewEvents$ = this.view$.filter(view => {
            return view.start.unix() !== this.view.start.unix()
                || view.end.unix() !== this.view.end.unix()
                || view.name !== this.view.name
                || view.timezone !== this.view.timezone;
        }).switchMap(view => {
            this.setLoading(true);
            this.view = view;
            const offset = this.timezoneOffset;
            const start = view.start.clone().subtract(offset, 'minutes');
            const end = view.end.clone().subtract(offset, 'minutes');
            return Observable.from(this.fetchEvents(start, end));
        }).share();

        this.viewEvents$.subscribe(events => {
            setTimeout(() => {
                this.viewEvents = events;
                if (this.timelineInitialized) {
                    this.timeline.changeView(this.view.name);
                    this.timeline.gotoDate(this.view.start);
                    this.$timeline.fullCalendar('option', 'visibleRange', {
                        start: this.view.start,
                        end: this.view.end,
                    });
                }
                this.$timeline.fullCalendar('refetchEvents');
            });
            this.setLoading(false);
        });

        this.viewTimeWorked$ = this.viewEvents$.combineLatest(this.users$, (events, users) => {
            return users.map(user => {
                const userEvents = events.filter(event => +event.resourceId === +user.id);
                let total = 0;
                const perDay: { [date: string]: number } = {};
                for (const event of userEvents) {
                    const start = moment.utc(event.start);
                    const end = moment.utc(event.end);
                    const time = end.diff(start);
                    total += time;

                    const date = start.format('YYYY-MM-DD');
                    if (perDay[date] !== undefined) {
                        perDay[date] += time;
                    } else {
                        perDay[date] = time;
                    }
                }

                return {
                    id: user.id,
                    total: total,
                    perDay: perDay,
                };
            });
        }).share();

        this.viewTimeWorked$.subscribe(data => {
            this.viewTimeWorked = data;
            this.showTimeWorkedOn();
        });

        const end = moment.utc();
        const start = moment.utc().subtract(1, 'day');
        this.latestEvents$ = Observable.from(this.fetchEvents(start, end));
        this.latestEvents$.subscribe(events => {
            this.latestEvents = events;
            this.showIsWorkingNow();
        });

        this.sortUsers$ = Observable.fromEvent(this.timeline.el.nativeElement, 'click')
            .map((event: MouseEvent) => event.target)
            .filter(element => element instanceof HTMLElement
                && $(element).hasClass('fc-cell-text')
                && $(element).parents('td.fc-resource-area th').length > 0)
            .map(element => $(element).text())
            .map(sort => {
                if (sort === 'Name') {
                    this.sortUsers = this.sortUsers === UsersSort.NameAsc
                        ? UsersSort.NameDesc : UsersSort.NameAsc;
                } else if (sort === 'Time Worked') {
                    this.sortUsers = this.sortUsers === UsersSort.TimeWorkedDesc
                        ? UsersSort.TimeWorkedAsc : UsersSort.TimeWorkedDesc;
                }
                return this.sortUsers;
            }).startWith(UsersSort.NameAsc).share();

        this.sortedUsers$ = this.sortUsers$.combineLatest(this.selectedUsers$, this.viewTimeWorked$, (sort, users, worked) => {
            return users.sort((a, b) => {
                switch (sort) {
                    default:
                    case UsersSort.NameAsc:
                        return a.title.localeCompare(b.title);
                    case UsersSort.NameDesc:
                        return b.title.localeCompare(a.title);
                    case UsersSort.TimeWorkedAsc: {
                        const aTimeWorked = worked.find(item => +item.id === +a.id);
                        const bTimeWorked = worked.find(item => +item.id === +b.id);
                        const aTime = aTimeWorked !== undefined ? aTimeWorked.total : 0;
                        const bTime = bTimeWorked !== undefined ? bTimeWorked.total : 0;
                        return aTime - bTime;
                    }
                    case UsersSort.TimeWorkedDesc: {
                        const aTimeWorked = worked.find(item => +item.id === +a.id);
                        const bTimeWorked = worked.find(item => +item.id === +b.id);
                        const aTime = aTimeWorked !== undefined ? aTimeWorked.total : 0;
                        const bTime = bTimeWorked !== undefined ? bTimeWorked.total : 0;
                        return bTime - aTime;
                    }
                }
            });
        }).share();

        this.sortedUsers$.subscribe(users => {
            this.selectedUsers = users;
            this.$timeline.fullCalendar('refetchResources');
        });

        this.timelineOptions = {
            defaultView: this.defaultView,
            now: moment.utc().startOf('day'),
            timezone: 'UTC',
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
                    text: (resource: ResourceInput) => {
                        const timeWorked = this.viewTimeWorked.find(data => +data.id === +resource.id);
                        const time = timeWorked !== undefined ? timeWorked.total : 0;
                        return this.formatDurationString(time);
                    },
                },
            ],
            resources: async (callback) => {
                callback(this.selectedUsers);
            },
            displayEventTime: false,
            events: (start, end, timezone, callback) => callback(this.viewEvents),
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
                        const columnWidth = $(dayColumnElement).width() - 4;

                        const html = rows.map(userRowElement => {
                            const userId = $(userRowElement).data('resource-id');

                            // Calculate time worked by this user per this day.
                            const timeWorked = this.viewTimeWorked.find(item => +item.id === +userId);
                            const time = timeWorked !== undefined && timeWorked.perDay[date] !== undefined
                                ? timeWorked.perDay[date] : 0;
                            const msIn24Hours = 24 * 60 * 60 * 1000;
                            const progress = time / msIn24Hours;
                            const percent = Math.round(100 * progress);
                            const timeString = this.formatDurationString(time);

                            const topOffset = $(userRowElement).position().top;
                            const progressWrapperClass = time < 10e-3 ? 'progress-wrapper_empty' : '';

                            return `
<div class="progress-wrapper ${progressWrapperClass}" style="top: ${topOffset}px; width: ${columnWidth}px;">
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <p>${timeString}</p>
</div>`;
                        }).join('');
                        $(dayColumnElement).html(html);
                    });
                }

                this.showIsWorkingNow();
                this.showTimeWorkedOn();

                $('.fc-resource-area th .fc-cell-text').removeClass('sort-asc');
                $('.fc-resource-area th .fc-cell-text').removeClass('sort-desc');
    
                switch (this.sortUsers) {
                    case UsersSort.NameAsc:
                        $('.fc-resource-area th:nth-child(2) .fc-cell-text').addClass('sort-asc');
                        break;
    
                    case UsersSort.NameDesc:
                        $('.fc-resource-area th:nth-child(2) .fc-cell-text').addClass('sort-desc');
                        break;
                    
                    case UsersSort.TimeWorkedAsc:
                        $('.fc-resource-area th:nth-child(3) .fc-cell-text').addClass('sort-asc');
                        break;
    
                    case UsersSort.TimeWorkedDesc:
                        $('.fc-resource-area th:nth-child(3) .fc-cell-text').addClass('sort-desc');
                        break;
                }

                this.timelineInitialized = true;
            },
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        };
    }

    formatDurationString(time: number) {
        const duration = moment.duration(time);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }

    showIsWorkingNow() {
        const $rows = $('.fc-resource-area tr[data-resource-id]', this.$timeline);
        $rows.each((index, row) => {
            const $row = $(row);
            const userId = $row.data('resource-id');

            const time = moment.utc().subtract(10, 'minutes');
            const events = this.latestEvents.filter(event => +event.resourceId === +userId
                && moment.utc(event.end).diff(time) > 0);
            const $cell = $('td:nth-child(1) .fc-cell-text', $row);

            if (events.length > 0) {
                $cell.addClass('is_working_now');
            } else {
                $cell.removeClass('is_working_now');
            }
        });
    }

    showTimeWorkedOn() {
        const $rows = $('.fc-resource-area tr[data-resource-id]', this.$timeline);
        $rows.each((index, row) => {
            const $row = $(row);
            const userId = $row.data('resource-id');
            const timeWorked = this.viewTimeWorked.find(item => +item.id === +userId);
            const time = timeWorked !== undefined ? timeWorked.total : 0;
            const timeWorkedString = this.formatDurationString(time);
            const $cell = $('td:nth-child(3) .fc-cell-text', $row);
            $cell.text(timeWorkedString);

            if (time < 10e-3) {
                $row.addClass('not_worked');
            } else {
                $row.removeClass('not_worked');
            }

            const lastUserEvents = this.latestEvents.filter(event => +event.resourceId === +userId);
            if (lastUserEvents.length > 0) {
                const lastUserEvent = lastUserEvents[lastUserEvents.length - 1];
                const eventEnd = moment(lastUserEvent.end);
                const $nameCell = $('td:nth-child(2) .fc-cell-text', $row);
                $nameCell.append('<p class="last-worked">Last worked ' + eventEnd.from(moment.utc().add(this.timezoneOffset, 'minutes')) + '</p>');
            }
        });
    }

    exportCSV() {
        const $timeline = this.$timeline;

        const view = $timeline.fullCalendar('getView');

        const $rows = $('.fc-resource-area tr[data-resource-id]', $timeline);
        const rows = $.makeArray($rows);

        const $days = $('.fc-day[data-date]', $timeline);
        const days = $.makeArray($days);

        let header = ['"Name"', '"Time Worked"'];
        if (view.name !== 'timelineDay') {
            const daysLabels = days.map(day => {
                const date = $(day).data('date');
                const dateString = (moment as any).tz(date, this.view.timezone).format('YYYY-MM-DD');
                return `"${dateString}"`;
            });
            header = header.concat(daysLabels);
        }

        const lines = rows.map(row => {
            const userId = $(row).data('resource-id');
            const user = this.$timeline.fullCalendar('getResourceById', userId);

            const timeWorked = this.viewTimeWorked.find(item => +item.id === +userId);
            const time = timeWorked !== undefined ? timeWorked.total : 0;
            const timeHours = moment.duration(time).asHours().toFixed(2);

            let cells = [`"${user.title}"`, `"${timeHours}"`];
            if (view.name !== 'timelineDay') {
                const daysData = days.map(day => {
                    const date = $(day).data('date');

                    // Calculate time worked by this user per this day.
                    const timeWorked = this.viewTimeWorked.find(item => +item.id === +userId);
                    const time = timeWorked !== undefined && timeWorked.perDay[date] !== undefined
                        ? timeWorked.perDay[date] : 0;
                    const timeHours = moment.duration(time).asHours().toFixed(2);
                    return `"${timeHours}"`;
                });
                cells = cells.concat(daysData);
            }

            return cells.join(',');
        });

        const content = 'data:text/csv;charset=utf-8,' + header.join(',') + '\n' + lines.join('\n');
        window.open(encodeURI(content));
    }

    setLoading(loading: boolean = true) {
        setTimeout(() => this.loading = loading);
    }
}
