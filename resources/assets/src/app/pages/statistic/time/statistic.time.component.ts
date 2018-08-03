import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Router} from "@angular/router";
import {Screenshot} from "../../../models/screenshot.model";
import {AllowedActionsService} from "../../roles/allowed-actions.service";
import {UsersService} from '../../users/users.service';
import {TimeIntervalsService} from '../../timeintervals/timeintervals.service';
import {User} from '../../../models/user.model';
import {TimeInterval} from '../../../models/timeinterval.model';
import 'fullcalendar';
import 'fullcalendar-scheduler';

@Component({
    selector: 'app-statistic-time',
    templateUrl: './statistic.time.component.html',
    styleUrls: ['../../items.component.scss']
})
export class StatisticTimeComponent implements OnInit {
    @ViewChild("fileInput") fileInput;

    public item: Screenshot = new Screenshot();
    public userList: User;
    public timeintervalList: TimeInterval;

    header: any;
    events: any[];
    options: any;

    constructor(api: ApiService,
                private userService: UsersService,
                private timeintervalService: TimeIntervalsService,
                router: Router,
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
            center: 'prev next',
            right: 'timelineDay'
        };

        this.events = [
            { id: '0', resourceId: 'a', start: '2018-08-03T02:00:00', end: '2018-08-03T07:00:00', title: 'event 1' },
            { id: '1', resourceId: 'b', start: '2018-08-03T02:00:00', end: '2018-08-03T07:00:00', title: 'event 1' },
            { id: '2', resourceId: 'c', start: '2018-08-03T05:00:00', end: '2018-08-03T22:00:00', title: 'event 2' },
        ];

        this.options = {
            defaultView: 'timelineDay',
            resourceLabelText: 'Rooms',
            resources: [
                { id: 'a', title: 'Auditorium A' },
                { id: 'b', title: 'Auditorium B' },
                { id: 'c', title: 'Auditorium C' },
            ],
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
