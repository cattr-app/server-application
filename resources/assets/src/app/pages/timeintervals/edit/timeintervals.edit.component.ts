import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TimeInterval} from "../../../models/timeinterval.model";
import {Router, ActivatedRoute} from "@angular/router";
import {TimeIntervalsService} from "../timeintervals.service";
import {ItemsEditComponent} from "../../items.edit.component";

@Component({
    selector: 'app-timeintervals-edit',
    templateUrl: './timeintervals.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TimeIntervalsEditComponent extends ItemsEditComponent implements OnInit {

    public item: TimeInterval = new TimeInterval();

    constructor(api: ApiService,
                timeIntervalService: TimeIntervalsService,
                activatedRoute: ActivatedRoute,
                router: Router) {
        super(api, timeIntervalService, activatedRoute, router)
    }

    prepareData() {
        return {
            'task_id': this.item.task_id,
            'start_at': this.item.start_at,
            'end_at': this.item.end_at
        }
    }
}
