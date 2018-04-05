import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TimeInterval} from "../../../models/timeinterval.model";
import {Router} from "@angular/router";
import {TimeIntervalsService} from "../timeintervals.service";
import {ItemsCreateComponent} from "../../items.create.component";
import {AllowedActionsService} from "../../roles/allowed-actions.service";

@Component({
    selector: 'app-timeintervals-create',
    templateUrl: './timeintervals.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TimeIntervalsCreateComponent extends ItemsCreateComponent implements OnInit {

    public item: TimeInterval = new TimeInterval();

    constructor(api: ApiService,
                timeIntervalService: TimeIntervalsService,
                router: Router,
                allowedService: AllowedActionsService,) {
        super(api, timeIntervalService, router, allowedService);
    }

    prepareData() {
        return {
            'task_id': this.item.task_id,
            'start_at': this.item.start_at,
            'end_at': this.item.end_at,
            'count_mouse': this.item.count_mouse,
            'count_keyboard': this.item.count_keyboard
        }
    }
}
