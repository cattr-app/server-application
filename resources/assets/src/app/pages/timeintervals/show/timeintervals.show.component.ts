import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {TimeIntervalsService} from "../timeintervals.service";
import {TimeInterval} from "../../../models/timeinterval.model";
import {ItemsShowComponent} from "../../items.show.component";

@Component({
    selector: 'app-timeintervals-show',
    templateUrl: './timeintervals.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TimeIntervalsShowComponent extends ItemsShowComponent implements OnInit {

    public item: TimeInterval = new TimeInterval();

    constructor(api: ApiService,
                timeIntervalService: TimeIntervalsService,
                router: ActivatedRoute) {
        super(api, timeIntervalService, router);
    }
}
