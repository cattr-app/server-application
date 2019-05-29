import { Component, OnInit, OnDestroy, ViewChild, AfterViewInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

import { UserSelectorComponent } from '../../../user-selector/user-selector.component';
import { DateRangeSelectorComponent } from '../../../date-range-selector/date-range-selector.component';
import { ProjectSelectorComponent } from '../../../project-selector/project-selector.component';

import { ApiService } from '../../../api/api.service';
import { ProjectReportService } from './projectsreport.service';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

import * as moment from 'moment';
import 'moment-timezone';

import * as XLSX from 'xlsx';

import * as jsPDF from 'jspdf';
import 'jspdf-autotable';
import { font } from '../../../../Roboto-Regular';

interface TaskData {
    id: number;
    project_id: number;
    user_id: number;
    task_name: string;
    duration: number;
    dates: { date: string, duration: number }[];
    expanded?: boolean;
    loading?: boolean;
};

interface UserData {
    id: number;
    full_name: string;
    avatar: string;
    tasks: TaskData[];
    tasks_time: number;
    expanded?: boolean;
};

interface ProjectData {
    id: number;
    name: string;
    users: UserData[];
    project_time: number;
};

@Component({
    selector: 'app-statistic-time',
    templateUrl: './projectsreport.component.html',
    styleUrls: ['../../items.component.scss', './projectsreport.component.scss']
})
export class ProjectsreportComponent implements OnInit, OnDestroy, AfterViewInit {
    @ViewChild('userSelect') userSelect: UserSelectorComponent;
    @ViewChild('projectSelect') projectSelect: ProjectSelectorComponent;
    @ViewChild('dateRangeSelector') dateRangeSelector: DateRangeSelectorComponent;

    // Used to show loading indicators.
    loading = true;
    projectsLoading = true;

    report: ProjectData[] = [];

    constructor(protected api: ApiService,
        protected projectReportService: ProjectReportService,
        protected allowedAction: AllowedActionsService,
        protected route: ActivatedRoute,
    ) { }

    readonly defaultView = 'timelineDay';
    readonly formatDate = 'YYYY-MM-DD';

    ngOnInit() {
    }

    ngAfterViewInit() {
        const selectedUsers$ = (this.userSelect.changed.asObservable())
            .map(users => users.filter(user => user.id !== -1)).share();

        const selectedProjects$ = (this.projectSelect.changed.asObservable())
            .map(projects => projects.filter(project => project.id !== -1)).share();

        const range$ = this.dateRangeSelector.rangeChanged.asObservable().share();

        const report$ = range$.combineLatest(selectedUsers$, selectedProjects$, (range, users, projects) => {
            const start = range.start.format(this.formatDate);
            const end = range.end.format(this.formatDate);
            const userIds = users.map(user => user.id);
            const projectIds = projects.map(project => project.id);
            return { userIds, projectIds, start, end };
        }).switchMap(data => this.fetchReport(data));
        report$.subscribe(data => {
            this.report = data;
        });
    }

    projectsLoaded(projects: any[]) {
        // Get preselected values from the query.
        const projectId = this.route.snapshot.queryParamMap.get('project');
        const startDate = this.route.snapshot.queryParamMap.get('start');
        const endDate = this.route.snapshot.queryParamMap.get('end');

        setTimeout(() => {
            if (projectId) {
                // Select a project specified by the query parameter.
                this.projectSelect.select([+projectId]);
            }

            if (startDate && endDate) {
                this.dateRangeSelector.setMode('range');
                this.dateRangeSelector.setStart(moment.utc(startDate));
                this.dateRangeSelector.setEnd(moment.utc(endDate));
                this.dateRangeSelector.applyChanges();
            }
        });
    }

    // Fetches report from the API.
    fetchReport({
        userIds,
        projectIds,
        start,
        end,
    }: {
        userIds: number[],
        projectIds: number[],
        start: string,
        end: string,
    }) {
        const params = {
            uids: userIds,
            pids: projectIds,
            start_at: start,
            end_at: end,
            type: 'report',
        };

        this.loading = true;
        return new Promise<ProjectData[]>(resolve => {
            this.projectReportService.getItems((report: ProjectData[]) => {
                // Add data for view to the response.
                report = report.map(project => {
                    project.users = project.users.map(user => {
                        user.tasks = user.tasks.map(task => {
                            task.dates = [];
                            task.expanded = false;
                            return task;
                        });
                        user.expanded = false;
                        return user;
                    });
                    return project;
                });

                this.loading = false;
                resolve(report);
            }, params);
        });
    }

    fetchTaskDates({
        uid,
        tid,
        start,
        end,
    }: {
        uid: number,
        tid: number,
        start: string,
        end: string,
    }) {
        return this.projectReportService.getTaskDates(uid, tid, start, end);
    }

    async expandTask(task: TaskData) {
        task.expanded = !task.expanded;

        if (task.expanded && !task.dates.length) {
            task.loading = true;
            const dates = await this.fetchTaskDates({
                uid: task.user_id,
                tid: task.id,
                start: this.dateRangeSelector.start.format(this.formatDate),
                end: this.dateRangeSelector.end.format(this.formatDate),
            });

            task.loading = false;
            task.dates = dates;
        }
    }

    get isManager() {
        return this.allowedAction.can('project-report/manager_access');
    }

    formatDurationString(time: number) {
        const duration = moment.duration(+time, 'seconds');
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        return `${hours}h ${minutes}m`;
    }

    formatDurationStringForExport(time: number) {
        const duration = moment.duration(+time, 'seconds');
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        const seconds = Math.floor(duration.asSeconds()) - 60 * 60 * hours - 60 * minutes;
        const hoursStr = (hours > 9 ? '' : '0') + hours;
        const minutesStr = (minutes > 9 ? '' : '0') + minutes;
        const secondsStr = (seconds > 9 ? '' : '0') + seconds;
        return `${hoursStr}:${minutesStr}:${secondsStr}`;
    }

    exportCSV() {
        let header = ['"Project"', '"Name"', '"Task"', '"Time"', '"Time (decimal)"'];
        let lines = [];

        this.report.forEach(project => {
            const proj_name = `"${project.name.replace(/"/g, '""')}"`;

            project.users.forEach(user => {
                const user_name = `"${user.full_name.replace(/"/g, '""')}"`;

                user.tasks.forEach(task => {
                    const task_name = `"${task.task_name.replace(/"/g, '""')}"`;
                    const time = this.formatDurationStringForExport(task.duration);
                    const duration = moment.duration(task.duration, 'seconds');
                    const timeDecimal = duration.asHours().toFixed(4);
                    lines.push([proj_name, user_name, task_name, `"${time}"`, timeDecimal].join(','));
                });
            });
        });

        const total = this.report.reduce((total, project) => total + project.project_time, 0);
        const time = this.formatDurationStringForExport(total);
        const duration = moment.duration(total, 'seconds');
        const timeDecimal = duration.asHours().toFixed(4);
        lines.push(['""', '""', '"Total"', `"${time}"`, timeDecimal].join(','));

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

    protected export() {
        const header = ['Project', 'Name', 'Task', 'Time', 'Time (decimal)'];
        const data = [];

        this.report.forEach(project => {
            const proj_name = project.name;

            project.users.forEach(user => {
                const user_name = user.full_name;

                user.tasks.forEach(task => {
                    const task_name = task.task_name;
                    const time = this.formatDurationStringForExport(task.duration);
                    const duration = moment.duration(task.duration, 'seconds');
                    const timeDecimal = duration.asHours();
                    data.push([proj_name, user_name, task_name, time, timeDecimal]);
                });
            });
        });

        const total = this.report.reduce((total, project) => total + project.project_time, 0);
        const time = this.formatDurationStringForExport(total);
        const duration = moment.duration(total, 'seconds');
        const timeDecimal = duration.asHours();
        data.push(['', '', 'Total', time, timeDecimal]);

        return {
            header,
            data,
        };
    }

    exportXLSX() {
        const { header, data } = this.export();
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([header, ...data]);

        // Set columns width
        ws['!cols'] = [
            { wch: 40 },
            { wch: 25 },
            { wch: 100 },
            { wch: 10 },
            { wch: 15 },
        ];

        // Format numbers
        const cellNames = Object.keys(ws).filter(key => !key.startsWith('!'));
        cellNames.map(name => ws[name]).forEach(cell => {
            if (cell.t === 'n') {
                cell.z = '0.0000';
            }
        });

        XLSX.utils.book_append_sheet(wb, ws);
        XLSX.writeFile(wb, 'wb.xlsx');
    }

    exportPDF() {
        const { header, data } = this.export();

        // Format time decimal
        data.forEach(row => {
            if (isFinite(row[4])) {
                row[4] = row[4].toFixed(4);
            }
        });

        const doc = new jsPDF();
        doc.addFileToVFS('Roboto-Regular.ttf', font);
        doc.addFont('Roboto-Regular.ttf', 'Roboto-Regular', 'normal');
        doc.setFont('Roboto-Regular');
        (doc as any).autoTable({
            head: [header],
            body: data,
            styles: {
                font: 'Roboto-Regular',
            },
            columnStyles: {
                0: { minCellWidth: 50 },
                1: { minCellWidth: 40 },
                2: { minCellWidth: 50 },
                3: { minCellWidth: 20 },
                4: { minCellWidth: 30 },
            },
        });
        doc.save('table.pdf');
    }

    cleanupParams(): string[] {
        return [

            'userSelect',
            'projectSelect',
            'dateRangeSelector',
            'loading',
            'projectsLoading',
            'report',
            'api',
            'projectReportService',
            'allowedAction',
            'route',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
