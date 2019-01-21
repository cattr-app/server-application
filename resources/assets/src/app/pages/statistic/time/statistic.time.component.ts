import { Component, OnInit, ViewChild, Output, EventEmitter, ChangeDetectorRef, OnDestroy } from '@angular/core';
import { PopoverDirective } from 'ngx-bootstrap';

import { ApiService } from '../../../api/api.service';
import { UsersService } from '../../users/users.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { TimeDurationService } from './statistic.time.service';
import { TasksService } from '../../tasks/tasks.service';
import { ProjectsService } from '../../projects/projects.service';
import { ScreenshotsService } from '../../screenshots/screenshots.service';

import { UserSelectorComponent } from '../../../user-selector/user-selector.component';
import { DateRangeSelectorComponent, Range } from '../../../date-range-selector/date-range-selector.component';

import { User } from '../../../models/user.model';
import { TimeInterval } from '../../../models/timeinterval.model';
import { TimeDuration } from '../../../models/timeduration.model';
import { Task } from '../../../models/task.model';
import { Project } from '../../../models/project.model';
import { Screenshot } from '../../../models/screenshot.model';

import * as $ from 'jquery';
import * as moment from 'moment';
import 'moment-timezone';

import 'fullcalendar';
import 'fullcalendar-scheduler';
import { EventObjectInput, View } from 'fullcalendar';
import { Schedule } from 'primeng/schedule';
import { ResourceInput } from 'fullcalendar-scheduler/src/exports';
import { TranslateService } from '@ngx-translate/core';

import { Observable, Subject } from 'rxjs/Rx';
import 'rxjs/operator/map';
import 'rxjs/operator/share';
import 'rxjs/operator/switchMap';
import { Router, ActivatedRoute } from '@angular/router';

enum UsersSort {
    NameAsc,
    NameDesc,
    TimeWorkedAsc,
    TimeWorkedDesc
}

interface TimeWorkedDay {
    total: number;
    events: EventObjectInput[];
}

interface TimeWorked {
    id: string,
    total: number,
    perDay: { [date: string]: TimeWorkedDay },
}

function debounce(f, delay) {
    let delayed = false;
    let delayedArgs = null;
    return (...args) => {
        delayedArgs = args;
        if (!delayed) {
            delayed = true;
            setTimeout(() => {
                delayed = false;
                f.apply(this, delayedArgs);
            }, delay);
        }
    };
}

@Component({
    selector: 'app-statistic-time',
    templateUrl: './statistic.time.component.html',
    styleUrls: ['../../items.component.scss', './statistic.time.component.scss']
})
export class StatisticTimeComponent implements OnInit, OnDestroy {
    @ViewChild('timeline') timeline: Schedule;
    @ViewChild('userSelect') userSelect: UserSelectorComponent;
    @ViewChild('dateRangeSelector') dateRangeSelector: DateRangeSelectorComponent;
    @ViewChild('clickPopover') clickPopover: PopoverDirective;
    @ViewChild('hoverPopover') hoverPopover: PopoverDirective;

    @Output() onSelectionChanged = new EventEmitter<TimeInterval[]>();

    selectedIntervals: TimeInterval[] = [];

    loading: boolean = true;
    popoverLoading: boolean = true;
    clickPopoverProject: Project = null;
    clickPopoverTask: Task = null;
    clickPopoverScreenshot: Screenshot = null;
    hoverPopoverProject: Project = null;
    hoverPopoverTask: Task = null;
    hoverPopoverEvent: EventObjectInput = null;
    hoverPopoverTime: number = 0;
    timelineInitialized: boolean = false;
    timelineOptions: any;
    updateInterval: any = null;

    readonly defaultView = 'timelineDay';

    view: string = this.defaultView;
    range: Range;
    timezone: string = '';
    viewEvents: EventObjectInput[] = [];
    viewEventsTasks: Task[] = [];
    viewEventsProjects: Project[] = [];
    viewTimeWorked: TimeWorked[] = [];
    latestEvents: EventObjectInput[] = [];
    latestEventsTasks: Task[] = [];
    latestEventsProjects: Project[] = [];
    users: ResourceInput[] = [];
    selectedUsers: ResourceInput[] = [];
    sortUsers: UsersSort = UsersSort.NameAsc;

    update$ = new Subject<Range>();
    viewRange$: Observable<Range>;
    viewEvents$: Observable<EventObjectInput[]>;
    viewEventsTasks$: Observable<Task[]>;
    viewEventsProjects$: Observable<Project[]>;
    viewTimeWorked$: Observable<TimeWorked[]>;
    latestEvents$: Observable<EventObjectInput[]>;
    latestEventsTasks$: Observable<Task[]>;
    latestEventsProjects$: Observable<Project[]>;
    selectedUsers$: Observable<ResourceInput[]>;
    sortUsers$: Observable<UsersSort>;
    sortedUsers$: Observable<ResourceInput[]>;

    eventFilter: string | Task | Project = '';

    constructor(private api: ApiService,
        private userService: UsersService,
        private timeintervalService: TimeIntervalsService,
        private timeDurationService: TimeDurationService,
        private taskService: TasksService,
        private projectService: ProjectsService,
        private screenshotService: ScreenshotsService,
        private translate: TranslateService,
        private cdr: ChangeDetectorRef,
        private router: Router,
        private activatedRoute: ActivatedRoute,
    ) {
        this.updateResourceInfo = debounce(this.updateResourceInfo.bind(this), 100);
    }

    readonly datePickerFormat = 'YYYY-MM-DD';

    get $timeline(): JQuery<any> {
        if (!this.timelineInitialized) {
            return null;
        }

        return $(this.timeline.el.nativeElement).children();
    }

    get timezoneOffset(): number {
        return -(moment as any).tz.zone(this.timezone).utcOffset(this.range.start);
    }

    get clickPopoverText(): string {
        const task = this.clickPopoverProject !== null ? this.clickPopoverProject.name : '';
        const proj = this.clickPopoverTask !== null ? this.clickPopoverTask.task_name : '';
        return `${task} (${proj})`;
    }

    get hoverPopoverText(): string {
        const task = this.hoverPopoverProject !== null ? this.hoverPopoverProject.name : '';
        const proj = this.hoverPopoverTask !== null ? this.hoverPopoverTask.task_name : '';
        const time = this.formatDurationString(this.hoverPopoverTime);

        return `${task} (${proj})<br />${time}`;
    }

    fetchEvents(start: moment.Moment, end: moment.Moment): Promise<EventObjectInput[]> {
        if (this.view === 'timelineDay') {
            const params = {
                'start_at': ['>', start],
                'end_at': ['<', end],
            };

            return new Promise<EventObjectInput[]>((resolve) => {
                this.timeintervalService.getItems((intervals: TimeInterval[]) => {
                    const offset = this.timezoneOffset;
                    const events = intervals
                        .map(interval => {
                            const start = moment.utc(interval.start_at).add(offset, 'minutes');
                            const end = moment.utc(interval.end_at).add(offset, 'minutes');
                            return {
                                id: interval.id,
                                title: '',
                                resourceId: interval.user_id,
                                start: start,
                                end: end,
                                task_id: interval.task_id,
                                duration: end.diff(start),
                            } as EventObjectInput;
                        })
                        // Filter events with duration less than one second.
                        // Zero-duration events breaks fullcalendar.
                        .filter(event => event.duration > 1000);

                    resolve(events);
                }, params);
            });
        } else {
            const params = {
                'start_at': start,
                'end_at': end,
            };

            return new Promise<EventObjectInput[]>((resolve) => {
                this.timeDurationService.getItems((durations: TimeDuration[]) => {
                    const offset = this.timezoneOffset;
                    const events = durations.map(duration => {
                        const start = moment.utc(duration.date).add(offset, 'minutes');
                        const end = start.clone().add(duration.duration, 'seconds');
                        return {
                            title: '',
                            resourceId: duration.user_id,
                            start: start,
                            end: end,
                            duration: end.diff(start),
                        } as EventObjectInput;
                    });
                    resolve(events);
                }, params);
            });
        }
    }

    fetchTasks(ids) {
        const params = {
            'id': ['=', ids],
        };

        return new Promise<Task[]>(resolve => {
            this.taskService.getItems((tasks: Task[]) => {
                resolve(tasks);
            }, params);
        });
    }

    fetchProjects(ids) {
        const params = {
            'id': ['=', ids],
        };

        return new Promise<Project[]>(resolve => {
            this.projectService.getItems((projects: Project[]) => {
                resolve(projects);
            }, params);
        });
    }

    ngOnInit() {
        this.range = {
            start: moment.utc().startOf('day'),
            end: moment.utc().startOf('day').add(1, 'day'),
        };

        this.selectedUsers$ = this.userSelect.changed.asObservable().map(users => {
            return users.map(user => {
                return {
                    id: '' + user.id,
                    title: user.full_name,
                };
            });
        }).share();

        this.viewRange$ = this.dateRangeSelector.rangeChanged.asObservable();
        this.viewEvents$ = this.viewRange$.filter(range => {
            return range.start.diff(this.range.start) !== 0
                || range.end.diff(this.range.end) !== 0;
        }).merge(this.update$).switchMap(range => {
            this.setLoading(true);
            this.range = range;

            const offset = this.timezoneOffset;
            // Get date only and correct timezone.
            const startStr = this.range.start.format('YYYY-MM-DD');
            const endStr = this.range.end.format('YYYY-MM-DD');
            const start = moment.utc(startStr).subtract(offset, 'minutes');
            let end = moment.utc(endStr).subtract(offset, 'minutes');
            if (this.view === 'timelineRange') {
                end.add(1, 'day');
            }

            this.router.navigate([], {
                relativeTo: this.activatedRoute,
                queryParams: {
                    start: startStr,
                    end: endStr,
                    range: this.dateRangeSelector.mode,
                },
                queryParamsHandling: 'merge',
                replaceUrl: true,
            });

            return Observable.from(this.fetchEvents(start, end));
        }).share();

        this.viewEvents$.subscribe(events => {
            setTimeout(() => {
                this.viewEvents = events;
                const start = moment.utc(this.range.start.format('YYYY-MM-DD'));
                let end = moment.utc(this.range.end.format('YYYY-MM-DD'));
                if (this.view === 'timelineRange') {
                    end.add(1, 'day');
                }

                if (this.timelineInitialized) {
                    this.timeline.changeView(this.view);
                    this.timeline.gotoDate(start);
                    this.$timeline.fullCalendar('option', 'visibleRange', {
                        start,
                        end,
                    });
                    this.$timeline.fullCalendar('refetchEvents');
                }
            });
            this.setLoading(false);
        });

        this.viewEventsTasks$ = this.viewEvents$
            // Only needed for a day view.
            .filter(events => this.view === 'timelineDay')
            .switchMap(events => {
                const ids = events.map(event => event.task_id);
                const uniqueIds = Array.from(new Set(ids));
                return Observable.from(this.fetchTasks(uniqueIds));
            })
            .share();
        this.viewEventsTasks$.subscribe(tasks => {
            this.viewEventsTasks = tasks;
        });

        this.viewEventsProjects$ = this.viewEventsTasks$
            .switchMap(tasks => {
                const ids = tasks.map(task => task.project_id);
                const uniqueIds = Array.from(new Set(ids));
                return Observable.from(this.fetchProjects(uniqueIds));
            });
        this.viewEventsProjects$.subscribe(projects => {
            this.viewEventsProjects = projects;
        });

        this.viewTimeWorked$ = this.viewEvents$.combineLatest(this.selectedUsers$, (events, users) => {
            return users.map(user => {
                const userEvents = events.filter(event => +event.resourceId === +user.id);
                let total = 0;
                const perDay: { [date: string]: TimeWorkedDay } = {};
                for (const event of userEvents) {
                    const start = moment.utc(event.start);
                    total += event.duration;

                    const date = start.format('YYYY-MM-DD');
                    if (perDay[date] !== undefined) {
                        perDay[date].total += event.duration;
                        perDay[date].events.push(event);
                    } else {
                        perDay[date] = {
                            total: event.duration,
                            events: [event],
                        };
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
        });

        this.latestEvents$ = this.update$.startWith(this.range).switchMap(() => {
            const end = moment.utc();
            const start = end.clone().subtract(1, 'day');
            return Observable.from(this.fetchEvents(start, end));
        }).share();
        this.latestEvents$.subscribe(events => {
            this.latestEvents = events;
            this.updateResourceInfo();
        });

        this.latestEventsTasks$ = this.latestEvents$.switchMap(events => {
            const ids = events.map(event => event.task_id);
            const uniqueIds = Array.from(new Set(ids));
            return Observable.from(this.fetchTasks(uniqueIds));
        }).share();
        this.latestEventsTasks$.subscribe(tasks => {
            this.latestEventsTasks = tasks;
            this.updateResourceInfo();
        });

        this.latestEventsProjects$ = this.latestEventsTasks$.switchMap(tasks => {
            const ids = tasks.map(task => task.project_id);
            const uniqueIds = Array.from(new Set(ids));
            return Observable.from(this.fetchProjects(uniqueIds));
        });
        this.latestEventsProjects$.subscribe(projects => {
            this.latestEventsProjects = projects;
            this.updateResourceInfo();
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
                    this.loading = true;
                } else if (sort === 'Time Worked') {
                    this.sortUsers = this.sortUsers === UsersSort.TimeWorkedDesc
                        ? UsersSort.TimeWorkedAsc : UsersSort.TimeWorkedDesc;
                    this.loading = true;
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
            if (this.$timeline) {
                this.$timeline.fullCalendar('refetchResources');
            }
        });

        this.timelineOptions = {
            defaultView: this.defaultView,
            now: moment.utc().startOf('day'),
            timezone: 'UTC',
            firstDay: 1,
            themeSystem: 'bootstrap3',
            eventColor: '#2ab27b',
            locale: this.translate.getDefaultLang(),
            views: {
                timelineDay: {
                    type: 'timeline',
                    duration: { days: 1 },
                    slotDuration: { hours: 1 },
                    slotWidth: 50,
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
            resourceAreaWidth: '25%',
            resourceColumns: [
                {
                    labelText: '',
                    text: () => '',
                    width: '20px',
                },
                {
                    labelText: 'Name',
                    field: 'title',
                    //width: '120px',
                },
                {
                    labelText: 'Time Worked',
                    text: (resource: ResourceInput) => {
                        const timeWorked = this.viewTimeWorked.find(data => +data.id === +resource.id);
                        const time = timeWorked !== undefined ? timeWorked.total : 0;
                        return this.formatDurationString(time);
                    },
                    width: '100px',
                },
            ],
            resources: async (callback) => {
                callback(this.selectedUsers);
            },
            displayEventTime: false,
            events: (start, end, timezone, callback) => {
                // Load all actual intervals to the fullcalendar only on a day view.
                callback(this.view === 'timelineDay' ? this.viewEvents : []);
            },
            eventClick: (event, jsEvent, view: View) => {
                jsEvent.stopPropagation();

                this.clickPopover.hide();
                this.popoverLoading = true;

                this.clickPopoverTask = null;
                this.clickPopoverProject = null;
                this.clickPopoverScreenshot = null;

                // Get clicked time interval group.
                const userId = event.resourceId;
                const events = this.viewEvents.filter(ev => {
                    return +ev.resourceId === +userId;
                }).sort((a, b) => {
                    return moment.utc(a.start).diff(moment.utc(b.start));
                });

                const intervalIds = [event.id];
                const currentEventIndex = events.findIndex(ev => ev.id === event.id);
                for (let i = currentEventIndex + 1; i < events.length; ++i) {
                    const prev = events[i - 1];
                    const curr = events[i];
                    if (moment.utc(curr.start).diff(moment.utc(prev.end)) > 60 * 1000) {
                        break;
                    }
                    intervalIds.push(curr.id);
                }

                for (let i = currentEventIndex - 1; i >= 0; --i) {
                    const next = events[i + 1];
                    const curr = events[i];
                    if (moment.utc(next.start).diff(moment.utc(curr.end)) > 60 * 1000) {
                        break;
                    }
                    intervalIds.push(curr.id);
                }

                // Load time intervals.
                this.timeintervalService.getItems(result => {
                    this.setSelectedIntervals(result);
                }, {
                        id: ['=', intervalIds],
                    });

                const task = this.viewEventsTasks.find(task => +task.id === +event.task_id);
                if (task) {
                    this.clickPopoverTask = task;
                    const project = this.viewEventsProjects.find(project => +project.id === +task.project_id);
                    if (project) {
                        this.clickPopoverProject = project;
                    }
                }

                setTimeout(() => {
                    this.screenshotService.getItems((screenshots: Screenshot[]) => {
                        this.popoverLoading = false;
                        if (screenshots.length > 0) {
                            const screenshot = screenshots[0];
                            this.clickPopoverScreenshot = screenshot;
                        }
                    }, {
                            time_interval_id: event.id,
                        });
                });

                const eventPos = $(jsEvent.currentTarget).offset();
                const timelinePos = $('.statistics__timeline').offset();
                const x = eventPos.left - timelinePos.left;
                const y = eventPos.top - timelinePos.top;

                const width = 250;
                const timelineWidth = $('.statistics__timeline').width();
                const arrowOnRight = timelineWidth - x < width;

                const $popover = $('#clickPopover');
                $popover.css({
                    top: y,
                    left: x + (arrowOnRight ? -1 : 1) * width / 2,
                });
                this.clickPopover.containerClass = arrowOnRight ? 'arrow_right' : 'arrow_left';
                this.clickPopover.show();
            },
            eventMouseover: (event, jsEvent, view) => {
                this.hoverPopover.hide();

                this.hoverPopoverEvent = event;
                this.hoverPopoverTask = null;
                this.hoverPopoverProject = null;

                // Calculate time from last break.
                const userId = event.resourceId;
                const events = this.viewEvents.filter(ev => {
                    return +ev.resourceId === +userId;
                }).sort((a, b) => {
                    return moment.utc(a.start).diff(moment.utc(b.start));
                });

                let total = moment.utc(event.end).diff(moment.utc(event.start));
                const currentEventIndex = events.findIndex(ev => ev.id === event.id);
                for (let i = currentEventIndex + 1; i < events.length; ++i) {
                    const prev = events[i - 1];
                    const curr = events[i];
                    if (moment.utc(curr.start).diff(moment.utc(prev.end)) > 60 * 1000) {
                        break;
                    }
                    total += moment.utc(curr.end).diff(moment.utc(curr.start));
                }

                for (let i = currentEventIndex - 1; i >= 0; --i) {
                    const next = events[i + 1];
                    const curr = events[i];
                    if (moment.utc(next.start).diff(moment.utc(curr.end)) > 60 * 1000) {
                        break;
                    }
                    total += moment.utc(curr.end).diff(moment.utc(curr.start));
                }

                this.hoverPopoverTime = total;

                const task = this.viewEventsTasks.find(task => +task.id === +event.task_id);
                if (task) {
                    this.hoverPopoverTask = task;
                    const project = this.viewEventsProjects.find(project => +project.id === +task.project_id);
                    if (project) {
                        this.hoverPopoverProject = project;
                    }
                }

                const eventPos = $(jsEvent.currentTarget).offset();
                const timelinePos = $('.statistics__timeline').offset();
                const x = eventPos.left - timelinePos.left;
                const y = eventPos.top - timelinePos.top;

                const width = 250;
                const timelineWidth = $('.statistics__timeline').width();
                const arrowOnRight = timelineWidth - x < width;

                const $popover = $('#hoverPopover');
                $popover.css({
                    top: y,
                    left: x + (arrowOnRight ? -1 : 1) * width / 2,
                });
                this.hoverPopover.containerClass = arrowOnRight ? 'arrow_right' : 'arrow_left';
                this.hoverPopover.show();
            },
            eventMouseout: (event, jsEvent, view) => {
                this.hoverPopover.hide();
            },
            dayClick: (date, jsEvent, view, resourceObj) => {
                if (view.name !== 'timelineDay') {
                    const userEvents = this.viewEvents
                        .filter(event => +event.resourceId === +resourceObj.id)
                        .filter(this.filterEvent.bind(this));

                    const day = date.format('YYYY-MM-DD');
                    this.selectedIntervals = userEvents.filter(event => {
                        const start = moment.utc(event.start);
                        const eventDay = start.format('YYYY-MM-DD');
                        return eventDay === day;
                    }).map(event => event.interval)
                        .filter(interval => interval);
                    this.onSelectionChanged.emit(this.selectedIntervals);
                }
            },
            eventRender: (event, el, view: View) => {
                if (view.name !== 'timelineDay') {
                    return false;
                }

                return this.filterEvent(event);
            },
            viewRender: debounce((view: View) => {
                if (view.name !== 'timelineDay' && this.$timeline) {
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
                                ? timeWorked.perDay[date].events
                                    .filter(this.filterEvent.bind(this))
                                    .map(event => event.duration)
                                    .reduce((total, curr) => total += curr, 0)
                                : 0;
                            const msIn24Hours = 24 * 60 * 60 * 1000;
                            const progress = time / msIn24Hours;
                            const percent = Math.round(100 * progress);
                            const timeString = this.formatDurationString(time);

                            const topOffset = $(userRowElement).position().top;
                            const empty = time < 10e-3;
                            return empty
                                ? `
<div class="progress-wrapper progress-wrapper_empty" style="top: ${topOffset}px; width: ${columnWidth}px;">
    <div class="progress"></div>
    <p>${timeString}</p>
</div>` : `
<div class="progress-wrapper" style="top: ${topOffset}px; width: ${columnWidth}px;">
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <p>${timeString}</p>
</div>
`;
                        }).join('');
                        dayColumnElement.innerHTML = html;
                    });
                }

                this.updateResourceInfo();

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

                setTimeout(() => this.loading = false);
            }, 250),
            eventAfterAllRender: (view: View) => {
                this.timelineInitialized = true;
            },
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        };

        this.updateInterval = setInterval(() => {
            const offset = this.timezoneOffset;
            const start = this.range.start.format('YYYY-MM-DD');
            const today = moment.utc().add(offset, 'minutes').format('YYYY-MM-DD');
            if (this.view === 'timelineDay' && start === today) {
                this.update();
            }
        }, 60 * 1000);

        this.cdr.detectChanges();
    }

    ngOnDestroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }

    setMode(mode: string) {
        this.view = 'timeline' + mode[0].toUpperCase() + mode.slice(1);
    }

    userFilter(user: User) {
        return !!user.active;
    }

    formatDurationString(time: number) {
        const duration = moment.duration(time);
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }

    updateResourceInfo() {
        if (!this.$timeline) {
            return;
        }

        const $rows = $('.fc-resource-area tr[data-resource-id]', this.$timeline);
        const offset = this.timezoneOffset;
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

            const $nameCell = $('td:nth-child(2) .fc-cell-text', $row);
            $nameCell.find('.current-task, .current-proj, .last-worked').remove();
            if ($('.name', $nameCell).length === 0) {
                const name = $nameCell.text();
                $nameCell.empty();
                const $name = $(`<p class="name">${name}</p>`);
                $name.attr('title', name);
                $nameCell.append($name);
            }

            const lastUserEvents = this.latestEvents.filter(event => +event.resourceId === +userId);
            const hasWorkedToday = lastUserEvents.length > 0;
            if (hasWorkedToday) {
                const lastUserEvent = lastUserEvents[lastUserEvents.length - 1];
                const eventEnd = moment.utc(lastUserEvent.end);

                const $workingNowCell = $('td:nth-child(1) .fc-cell-text', $row);
                const now = moment.utc().add(offset, 'minutes').subtract(10, 'minutes');
                const isWorkingNow = eventEnd.diff(now) > 0;
                if (isWorkingNow) {
                    $workingNowCell.addClass('is_working_now');

                    const currentTask = this.latestEventsTasks.find(task => +task.id === +lastUserEvent.task_id);
                    if (currentTask !== undefined) {
                        const currentProject = this.latestEventsProjects
                            .find(proj => +proj.id === +currentTask.project_id);
                        if (currentProject !== undefined) {
                            const projectName = currentProject.name;
                            const projectUrl = 'projects/show/' + currentProject.id;
                            const $project = $(`<span class="current-proj"><a href="${projectUrl}">${projectName}</a></span>`);
                            $project.attr('title', projectName);
                            $nameCell.children('.name').append($project);
                        }

                        const taskName = currentTask.task_name;
                        const taskUrl = 'tasks/show/' + currentTask.id;
                        const $task = $(`<p class="current-task"><a href="${taskUrl}">${taskName}</a></p>`);
                        $task.attr('title', taskName);
                        $nameCell.append($task);
                    }
                } else {
                    $workingNowCell.removeClass('is_working_now');
                    const lastWorkedString = 'Last worked '
                        + eventEnd.from(moment.utc().add(offset, 'minutes'));
                    const $lastWorked = $(`<p class="last-worked">${lastWorkedString}</p>`);
                    $lastWorked.attr('title', lastWorkedString);
                    $nameCell.append($lastWorked);
                }
            }
        });
    }

    exportCSV() {
        if (!this.$timeline) {
            return;
        }

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
                const dateString = (moment as any).tz(date, this.timezone).format('YYYY-MM-DD');
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

            let cells = [`"${user.title.replace(/"/g, '""')}"`, `"${timeHours}"`];
            if (view.name !== 'timelineDay') {
                const daysData = days.map(day => {
                    const date = $(day).data('date');

                    // Calculate time worked by this user per this day.
                    const timeWorked = this.viewTimeWorked.find(item => +item.id === +userId);
                    const time = timeWorked !== undefined && timeWorked.perDay[date] !== undefined
                        ? timeWorked.perDay[date].total : 0;
                    const timeHours = moment.duration(time).asHours().toFixed(2);
                    return `"${timeHours}"`;
                });
                cells = cells.concat(daysData);
            }

            return cells.join(',');
        });

        const filename = 'data.csv';
        const content = header.join(',') + '\n' + lines.join('\n');
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        if (navigator.msSaveBlob) { // IE 10+
            navigator.msSaveBlob(blob, filename);
        } else {
            const link = document.createElement('a');
            if (link.download !== undefined) { // feature detection
                // Browsers that support HTML5 download attribute
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                window.open(encodeURI('data:text/csv;charset=utf-8,' + content));
            }
        }
    }

    setLoading(loading: boolean = true) {
        setTimeout(() => this.loading = loading);
    }

    setSelectedIntervals(intervals: TimeInterval[]) {
        this.selectedIntervals = intervals;
        this.onSelectionChanged.emit(intervals);
    }

    reload() {
        this.clickPopover.hide();
        this.hoverPopover.hide();
        this.selectedIntervals = [];
        this.$timeline.fullCalendar('refetchEvents');
    }

    filter(filter: string | Task | Project) {
        this.eventFilter = filter;
        this.$timeline.fullCalendar('refetchEvents');
    }

    filterEvent(event: EventObjectInput): boolean {
        if (typeof this.eventFilter === 'string' && this.eventFilter.length) {
            const filter = this.eventFilter.toUpperCase();
            const task = this.viewEventsTasks.find(task =>
                +task.id === +event.task_id);
            if (task) {
                const taskName = task.task_name.toUpperCase();
                if (taskName.indexOf(filter) !== -1) {
                    return true;
                }

                const project = this.viewEventsProjects.find(project =>
                    +project.id === +task.project_id);
                if (project && project.name.toUpperCase().indexOf(filter) !== -1) {
                    return true;
                }
            }

            return false;
        } else if (this.eventFilter instanceof Project) {
            const task = this.viewEventsTasks.find(task =>
                +task.id === +event.task_id);
            if (task) {
                return +task.project_id === +this.eventFilter.id;
            }

            return false;
        } else if (this.eventFilter instanceof Task) {
            return +event.task_id === +this.eventFilter.id;
        }

        return true;
    }

    update() {
        this.update$.next(this.range);
    }
}
