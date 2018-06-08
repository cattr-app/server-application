import {Component, DoCheck, IterableDiffers, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TimeIntervalsService} from '../timeintervals.service';
import {TimeInterval} from '../../../models/timeinterval.model';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-timentervals-list',
    templateUrl: './timeintervals.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TimeIntervalsListComponent extends ItemsListComponent implements OnInit, DoCheck {

    itemsArray: TimeInterval[] = [];
    p = 1;
    userId: any = '';
    differ: any;

    constructor(api: ApiService,
                timeIntervalService: TimeIntervalsService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,
                differs: IterableDiffers
    ) {
        super(api, timeIntervalService, modalService, allowedService);
        this.differ = differs.find([]).create(null);
    }

    ngOnInit() {
    }

    ngDoCheck() {
        const changeId = this.differ.diff([this.userId]);

        if (changeId) {
            this.itemService.getItems(this.setItems.bind(this), this.userId ? {'user_id': ['=', this.userId]} : null);
        }
    }
}
