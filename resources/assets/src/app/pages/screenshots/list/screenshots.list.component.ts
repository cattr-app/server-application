import {Component, DoCheck, IterableDiffers, OnInit, ViewChild, OnDestroy} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ScreenshotsService} from '../screenshots.service';
import { Screenshot } from '../../../models/screenshot.model';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-screenshots-list',
    templateUrl: './screenshots.list.component.html',
    styleUrls: ['./screenshots.list.component.css']
})
export class ScreenshotsListComponent implements OnInit, DoCheck, OnDestroy {
    @ViewChild('loading') element: any;

    userId: number = null;
    projectId: number = null;
    differUser: any;
    differProject: any;

    chunksize = 32;
    offset = 0;
    screenshots: Screenshot[] = [];
    screenshotLoading = false;
    scrollHandler: any = null;
    countFail = 0;

    constructor(protected api: ApiService,
                protected itemService: ScreenshotsService,
                protected allowedAction: AllowedActionsService,
                differs: IterableDiffers
    ) {
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
        const filter = {'with': 'timeInterval,timeInterval.task,timeInterval.user'};

        if (changeUserId || changeProjectId) {
            if (this.userId) {
                filter['user_id'] = ['=', this.userId];
            }

            if (this.projectId) {
                filter['project_id'] = ['=', this.projectId];
            }

            //this.itemService.getItems(this.setData.bind(this), filter ? filter : null);
        }
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    can(action: string ): boolean {
        return this.allowedAction.can(action);
    }

    getScreenshots(limit, offset, callback, userId) {
        const itemsArray: Screenshot[] = [];

        return this.api.send(
            'screenshots/list',
            {
                'user_id': userId,
                'limit': limit,
                'offset': offset,
                'with': 'timeInterval,timeInterval.task,timeInterval.task.project'
            },
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(new Screenshot(itemFromApi));
                });

                callback(itemsArray);
            });
    }

    setData(result) {
        if (result.length > 0) {
            this.offset += this.chunksize;

            for (const screenshot of result) {
                this.screenshots.push(new Screenshot(screenshot));
            }
        } else {
            this.countFail += 1;
        }

        this.screenshotLoading = false;
    }

    loadNext() {
        if (this.screenshotLoading || this.countFail > 3) {
          return;
        }

        const user: any = this.api.getUser() ? this.api.getUser() : null;
        this.screenshotLoading = true;
        this.getScreenshots(this.chunksize, this.offset, this.setData.bind(this), user.id);
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

    minutes(datetime) {
        const regex = /\d{4}-\d{2}-\d{2} \d{2}:(\d{2}:\d{2})/;
        const matches = datetime.match(regex);

        return matches[1];
    }
}
