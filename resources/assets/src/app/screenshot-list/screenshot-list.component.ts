import { Component, DoCheck, IterableDiffers, OnInit, ViewChild, OnDestroy, Input, IterableDiffer } from '@angular/core';
import { BsModalService } from 'ngx-bootstrap';

import { ApiService } from '../api/api.service';
import { ScreenshotsService } from '../pages/screenshots/screenshots.service';
import { AllowedActionsService } from '../pages/roles/allowed-actions.service';

import { ItemsListComponent } from '../pages/items.list.component';
import {LocalStorage} from '../api/storage.model';

import * as moment from 'moment';
@Component({
    selector: 'screenshot-list',
    templateUrl: './screenshot-list.component.html',
    styleUrls: ['./screenshot-list.component.scss']
})
export class ScreenshotListComponent extends ItemsListComponent implements OnInit, DoCheck, OnDestroy {
    @ViewChild('loading') loading: any;

    @Input() autoload: boolean = true;

    @Input() showDate: boolean = true;
    @Input() showUser: boolean = true;
    @Input() showProject: boolean = true;
    @Input() showTask: boolean = true;

    @Input() user_ids?: number[] = null;
    @Input() project_ids?: number[] = null;
    @Input() task_ids?: number[] = null;

    differUsers: IterableDiffer<number[]>;
    differProjects: IterableDiffer<number[]>;
    differTasks: IterableDiffer<number[]>;

    chunksize = 32;
    offset = 0;
    screenshotLoading = false;
    scrollHandler: any = null;
    countFail = 0;
    isAllLoaded = false;

    protected _itemsChunked = [];
    get itemsChunked() {
        if (this._itemsChunked.length > 0) {
            return this._itemsChunked;
        }

        const result = [];
        const chunkSize = 12;
        for (let i = 0, len = this.itemsArray.length; i < len; i += chunkSize) {
            result.push(this.itemsArray.slice(i, i + chunkSize));
        }

        return this._itemsChunked = result;
    }

    constructor(protected api: ApiService,
        protected itemService: ScreenshotsService,
        protected modalService: BsModalService,
        protected allowedAction: AllowedActionsService,
        differs: IterableDiffers,
    ) {
        super(api, itemService, modalService, allowedAction);
        this.differUsers = differs.find([]).create(null);
        this.differProjects = differs.find([]).create(null);
        this.differTasks = differs.find([]).create(null);
    }

    ngOnInit() {
        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngDoCheck() {
        const changeUserIds = this.differUsers.diff([this.user_ids]);
        const changeProjectIds = this.differProjects.diff([this.project_ids]);
        const changeTaskIds = this.differTasks.diff([this.task_ids]);

        if (changeUserIds || changeProjectIds || changeTaskIds) {
            this.offset = 0;
            this.setItems([]);
            this.countFail = 0;
            this.isAllLoaded = false;
            this.loadNext();
        }
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    onScrollDown() {
        if (!this.autoload) {
            return;
        }

        const block_Y_position = this.loading.nativeElement.offsetTop;
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

    formatTime(datetime?: string) {
        if (!datetime) {
            return null;
        }

        return moment.utc(datetime).local().format('DD.MM.YYYY HH:mm:ss');
    }

    loadNext() {
        if (this.screenshotLoading || this.countFail > 3) {
            return;
        }

        const params = {
            'with': 'timeInterval,timeInterval.task,timeInterval.task.project,timeInterval.user',
            'limit': this.chunksize,
            'offset': this.offset,
            'order_by': ['id', 'desc'],
        };

        let filterByUser = LocalStorage.getStorage().get(`filterByUserIN${ window.location.pathname }`);
        if (filterByUser instanceof Array && filterByUser.length > 0) {
            this.user_ids = filterByUser;
        }

        let filterByProject = LocalStorage.getStorage().get(`filterByProjectIN${ window.location.pathname }`);
        if (filterByProject instanceof Array && filterByProject.length > 0) {
            this.project_ids = filterByProject;
        }

        if (this.user_ids) {
            params['user_id'] = ['=', this.user_ids];
        }

        if (this.project_ids) {
            params['project_id'] = ['=', this.project_ids];
        }

        if (this.task_ids) {
            params['timeInterval.task_id'] = ['=', this.task_ids];
        }

        this.screenshotLoading = true;
        try {
            this.itemService.getItems(items => {
                if (items.length > 0) {
                    this.setItems(this.itemsArray.concat(items));
                    this.offset += this.chunksize;
                } else {
                    this.countFail += 1;
                }

                this.isAllLoaded = items.length < this.chunksize;
                this.screenshotLoading = false;
            }, params);
        } catch {
            this.countFail += 1;
            this.screenshotLoading = false;
        }
    }
}
