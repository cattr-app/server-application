import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { Router } from "@angular/router";
import { NgSelectComponent } from '@ng-select/ng-select';
import { ViewSwitcherComponent, ViewData } from './view-switcher/view-switcher.component';
import { PopoverDirective } from 'ngx-bootstrap';

import { ApiService } from '../../../api/api.service';
import { UsersService } from '../../users/users.service';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { TasksService } from '../../tasks/tasks.service';
import { ProjectsService } from '../../projects/projects.service';
import { ScreenshotsService } from '../../screenshots/screenshots.service';

import { User } from '../../../models/user.model';
import { TimeInterval } from '../../../models/timeinterval.model';
import { Task } from '../../../models/task.model';

import * as $ from 'jquery';
import * as moment from 'moment';
import 'moment-timezone';

import 'fullcalendar';
import 'fullcalendar-scheduler';
import { EventObjectInput, View } from 'fullcalendar';
import { Schedule } from 'primeng/schedule';
import { ResourceInput } from 'fullcalendar-scheduler/src/exports';

import { Observable } from 'rxjs/Rx';
import 'rxjs/operator/map';
import 'rxjs/operator/share';
import 'rxjs/operator/switchMap';
import { Project } from '../../../models/project.model';
import { Screenshot } from '../../../models/screenshot.model';

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
    @ViewChild('popover') popover: PopoverDirective;

    selectedUserIds: string[];

    loading: boolean = true;
    usersLoading: boolean = true;
    popoverLoading: boolean = true;
    popoverProject: Project = null;
    popoverTask: Task = null;
    popoverScreenshot: Screenshot = null;
    timelineInitialized: boolean = false;
    timelineOptions: any;

    view: ViewData;
    viewEvents: EventObjectInput[] = [];
    viewTimeWorked: TimeWorked[] = [];
    latestEvents: EventObjectInput[] = [];
    latestEventsTasks: Task[] = [];
    latestEventsProjects: Project[] = [];
    users: ResourceInput[] = [];
    selectedUsers: ResourceInput[] = [];
    sortUsers: UsersSort = UsersSort.NameAsc;

    view$: Observable<ViewData>;
    viewEvents$: Observable<EventObjectInput[]>;
    viewTimeWorked$: Observable<TimeWorked[]>;
    latestEvents$: Observable<EventObjectInput[]>;
    latestEventsTasks$: Observable<Task[]>;
    latestEventsProjects$: Observable<Project[]>;
    users$: Observable<ResourceInput[]>;
    selectedUsers$: Observable<ResourceInput[]>;
    sortUsers$: Observable<UsersSort>;
    sortedUsers$: Observable<ResourceInput[]>;

    constructor(private api: ApiService,
        private userService: UsersService,
        private timeintervalService: TimeIntervalsService,
        private taskService: TasksService,
        private projectService: ProjectsService,
        private screenshotService: ScreenshotsService,
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

    get popoverText(): string {
        const parts = [];
        parts.push(this.popoverProject !== null ? this.popoverProject.name : '');
        parts.push(this.popoverTask !== null ? this.popoverTask.task_name : '');
        return parts.join(' - ');
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
                        task_id: interval.task_id,
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
            this.updateResourceInfo();
        });

        const end = moment.utc();
        const start = end.clone().subtract(1, 'day');
        this.latestEvents$ = Observable.from(this.fetchEvents(start, end)).share();
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
            resourceAreaWidth: '360px',
            resourceColumns: [
                {
                    labelText: '',
                    text: () => '',
                    width: '40px',
                },
                {
                    labelText: 'Name',
                    field: 'title',
                    width: '180px',
                },
                {
                    labelText: 'Time Worked',
                    text: (resource: ResourceInput) => {
                        const timeWorked = this.viewTimeWorked.find(data => +data.id === +resource.id);
                        const time = timeWorked !== undefined ? timeWorked.total : 0;
                        return this.formatDurationString(time);
                    },
                    width: '140px',
                },
            ],
            resources: async (callback) => {
                callback(this.selectedUsers);
            },
            displayEventTime: false,
            events: (start, end, timezone, callback) => {
                // Load all actual intervals to the fullcalendar only on a day view.
                callback(this.view.name === 'timelineDay' ? this.viewEvents : []);
            },
            eventClick: (event, jsEvent, view: View) => {
                this.popover.hide();
                this.popoverLoading = true;

                this.popoverTask = null;
                this.popoverProject = null;
                this.popoverScreenshot = null;

                this.taskService.getItem(event.task_id, (task: Task) => {
                    this.popoverTask = task;
                    this.projectService.getItem(task.project_id, (project: Project) => {
                        this.popoverProject = project;
                    });
                });

                this.screenshotService.getItems((screenshots: Screenshot[]) => {
                    this.popoverLoading = false;
                    if (screenshots.length > 0) {
                        const screenshot = screenshots[0];
                        this.popoverScreenshot = screenshot;
                    }
                }, {
                    time_interval_id: event.id,
                });

                const eventPos = $(jsEvent.currentTarget).offset();
                const timelinePos = $('.statistics__timeline').offset();

                const $popover = $('#popover');
                $popover.css({
                    top: eventPos.top - timelinePos.top,
                    left: eventPos.left - timelinePos.left,
                });
                this.popover.show();
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

    updateResourceInfo() {
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
            const hasWorkedToday = lastUserEvents.length > 0;
            if (hasWorkedToday) {
                const lastUserEvent = lastUserEvents[lastUserEvents.length - 1];
                const eventEnd = moment.utc(lastUserEvent.end);
                const $nameCell = $('td:nth-child(2) .fc-cell-text', $row);
                $nameCell.children('.current-task, .last-worked').remove();

                const $workingNowCell = $('td:nth-child(1) .fc-cell-text', $row);
                const now = moment.utc().subtract(10, 'minutes');
                const isWorkingNow = eventEnd.diff(now) > 0;
                if (isWorkingNow) {
                    $workingNowCell.addClass('is_working_now');

                    const currentTask = this.latestEventsTasks.find(task => +task.id === +lastUserEvent.task_id);
                    if (currentTask !== undefined) {
                        const maxLength = 20;
                        const taskName = currentTask.task_name.length > maxLength
                            ? currentTask.task_name.substring(0, maxLength - 1) + '…'
                            : currentTask.task_name;

                        const currentProject = this.latestEventsProjects
                            .find(proj => +proj.id === +currentTask.project_id);
                        if (currentProject !== undefined) {
                            const projectName = currentProject.name.length > maxLength
                                ? currentProject.name.substring(0, maxLength - 1) + '…'
                                : currentProject.name;
                            $nameCell.append(`<p class="current-task">${taskName} (${projectName})</p>`);
                        } else {
                            $nameCell.append(`<p class="current-task">${taskName}</p>`);
                        }
                    }
                } else {
                    $workingNowCell.removeClass('is_working_now');
                    const lastWorkedString = eventEnd.from(moment.utc().add(this.timezoneOffset, 'minutes'));
                    $nameCell.append(`<p class="last-worked">Last worked ${lastWorkedString}</p>`);
                }
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
}
