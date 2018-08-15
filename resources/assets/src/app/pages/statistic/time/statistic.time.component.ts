import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Router} from "@angular/router";
import {Screenshot} from "../../../models/screenshot.model";
import {AllowedActionsService} from "../../roles/allowed-actions.service";
import {UsersService} from '../../users/users.service';
import {User} from '../../../models/user.model';
import {TimeInterval} from '../../../models/timeinterval.model';

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




    constructor(api: ApiService,
                private userService: UsersService,
                router: Router,
                allowedService: AllowedActionsService,) {

    }


    ngOnInit() {
        /**
         * @todo uncomment it, when data will be fill
         */
        // this.userService.getItems(this.onUsersGet.bind(this));
        // this.timeintervalService.getItems(this.onTimeIntervalGet.bind(this));
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
