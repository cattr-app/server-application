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
    userId: number = null;
    projectId: number = null;
    differUser: any;
    differProject: any;

    constructor(api: ApiService,
                itemService: ScreenshotsService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,
                differs: IterableDiffers
    ) {
        super(api, itemService, modalService, allowedService);
        this.differUser = differs.find([]).create(null);
        this.differProject = differs.find([]).create(null);
    }

    ngOnInit() {
    }

    ngDoCheck() {
        const changeUserId = this.differUser.diff([this.userId]);
        const changeProjectId = this.differProject.diff([this.projectId]);
        const filter = {};

        if (changeUserId || changeProjectId) {
            if (this.userId) {
                filter['user_id'] = ['=', this.userId];
            }

            if (this.projectId) {
                filter['project_id'] = ['=', this.projectId];
            }

            this.itemService.getItems(this.setItems.bind(this), filter ? filter : null);
        }
    }
}
