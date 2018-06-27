import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from '@angular/router';
import {TimeIntervalsService} from '../timeintervals.service';
import {TimeInterval} from '../../../models/timeinterval.model';
import {ItemsShowComponent} from '../../items.show.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-timeintervals-show',
    templateUrl: './timeintervals.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TimeIntervalsShowComponent extends ItemsShowComponent implements OnInit {

    public item: TimeInterval = new TimeInterval();

    constructor(protected api: ApiService,
                protected timeIntervalService: TimeIntervalsService,
                protected router: ActivatedRoute,
                allowService: AllowedActionsService
    ) {
        super(api, timeIntervalService, router, allowService);
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });
        const filters = {'with': 'user,task,screenshots'};

        this.timeIntervalService.getItem(this.id, this.setItem.bind(this), filters);
    }

    isEmptyObject(obj) {
        return (obj && (Object.keys(obj).length === 0));
    }

}
