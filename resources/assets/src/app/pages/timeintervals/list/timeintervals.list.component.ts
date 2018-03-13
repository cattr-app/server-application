import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TimeIntervalsService} from "../timeintervals.service";
import {TimeInterval} from "../../../models/timeinterval.model";
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from "../../items.list.component";

@Component({
    selector: 'app-timeintervals-list',
    templateUrl: './timeintervals.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TimeIntervalsListComponent extends ItemsListComponent implements OnInit {

    itemsArray: TimeInterval[] = [];
    p: number = 1;

    constructor(api: ApiService,
                timeIntervalService: TimeIntervalsService,
                modalService: BsModalService,) {
        super(api, timeIntervalService, modalService);
    }
}
