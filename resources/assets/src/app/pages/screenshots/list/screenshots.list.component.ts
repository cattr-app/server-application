import {Component, DoCheck, IterableDiffers, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {Screenshot} from '../../../models/screenshot.model';
import {ScreenshotsService} from '../screenshots.service';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-screenshots-list',
    templateUrl: './screenshots.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ScreenshotsListComponent extends ItemsListComponent implements OnInit, DoCheck {

    itemsArray: Screenshot[] = [];
    p = 1;
    userId: any = '';
    differ: any;

    constructor(api: ApiService,
                itemService: ScreenshotsService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,
                differs: IterableDiffers
    ) {
        super(api, itemService, modalService, allowedService);
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
