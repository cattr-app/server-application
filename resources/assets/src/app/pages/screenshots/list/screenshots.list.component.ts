import {Component, DoCheck, IterableDiffers, OnInit, ViewChild, OnDestroy} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ScreenshotsService} from '../screenshots.service';
import { BsModalService } from 'ngx-bootstrap';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

import { Screenshot } from '../../../models/screenshot.model';

import { ItemsListComponent } from '../../items.list.component';
import { splitMatchedQueriesDsl } from '@angular/core/src/view/util';

@Component({
    selector: 'app-screenshots-list',
    templateUrl: './screenshots.list.component.html',
    styleUrls: ['./screenshots.list.component.css']
})
export class ScreenshotsListComponent extends ItemsListComponent implements OnInit, DoCheck, OnDestroy {
    @ViewChild('loading') element: any;

    userId: number = null;
    projectId: number = null;
    differUser: any;
    differProject: any;

    chunksize = 32;
    offset = 0;
    screenshotLoading = false;
    scrollHandler: any = null;
    countFail = 0;

    protected _itemsChunked = [];
    get itemsChunked() {
        if (this._itemsChunked.length > 0) {
            return this._itemsChunked;
        }

        const result = [];
        const chunkSize = 6;
        for (let i = 0, len = this.itemsArray.length; i < len; i += chunkSize) {
            result.push(this.itemsArray.slice(i, i + chunkSize));
        }

        return this._itemsChunked = result;
    }

    constructor(protected api: ApiService,
                protected itemService: ScreenshotsService,
                protected modalService: BsModalService,
                protected allowedAction: AllowedActionsService,
                differs: IterableDiffers
    ) {
        super(api, itemService, modalService, allowedAction);
        this.differUser = differs.find([]).create(null);
        this.differProject = differs.find([]).create(null);
    }

    ngOnInit() {
        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngDoCheck() {
        const changeUserId = this.differUser.diff([this.userId]);
        const changeProjectId = this.differProject.diff([this.projectId]);

        if (changeUserId || changeProjectId) {
            this.offset = 0;
            this.setItems([]);
            this.countFail = 0;
            this.loadNext();
        }
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    onScrollDown() {
        const block_Y_position = this.element.nativeElement.offsetTop;
        const scroll_Y_top_position = window.scrollY;
        const windowHeight = window.innerHeight;
        const bottom_scroll_Y_position = scroll_Y_top_position + windowHeight;

        if (bottom_scroll_Y_position < block_Y_position) { // loading new screenshots doesn't needs
            return;
        }

        this.loadNext();
    }

    setItems(items) {
        super.setItems(items);
        this._itemsChunked = [];
    }

    minutes(datetime) {
        const regex = /\d{4}-\d{2}-\d{2} \d{2}:(\d{2}:\d{2})/;
        const matches = datetime.match(regex);

        return matches[1];
    }

    loadNext() {
        if (this.screenshotLoading || this.countFail > 3) {
          return;
        }

        const params = {
            'with': 'timeInterval,timeInterval.task,timeInterval.user',
            'limit': this.chunksize,
            'offset': this.offset,
        };

        if (this.userId) {
            params['user_id'] = ['=', this.userId];
        }

        if (this.projectId) {
            params['project_id'] = ['=', this.projectId];
        }

        this.screenshotLoading = true;
        try {
            this.itemService.getItems((items) => {
                if (items.length > 0) {
                    this.setItems(this.itemsArray.concat(items));
                    this.offset += this.chunksize;
                } else {
                    this.countFail += 1;
                }

                this.screenshotLoading = false;
            }, params);
        } catch {
            this.countFail += 1;
            this.screenshotLoading = false;
        }
    }
}
