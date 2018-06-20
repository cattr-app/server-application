import {Component, ViewChild, OnInit, OnDestroy} from '@angular/core';
import {ScreenshotsBlock} from '../../../models/screenshot.model';
import {ApiService} from '../../../api/api.service';
import {DashboardService} from '../dashboard.service';


@Component({
  selector: 'dashboard-screenshotlist',
  templateUrl: './screenshot.list.component.html',
  styleUrls: ['./screenshot.list.component.css']
})
export class ScreenshotListComponent implements OnInit, OnDestroy {

    @ViewChild('loading') element: any;
    chunksize = 32;
    offset = 0;
    blocks: ScreenshotsBlock[] = [];
    screenshotLoading = false;
    scrollHandler: any = null;
    countFail = 0;

    constructor(protected api: ApiService, protected dashboardService: DashboardService) {
    }

    ngOnInit() {
        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    loadNext() {
        if (this.screenshotLoading || this.countFail > 3) {
          return;
        }

        const user: any = this.api.getUser() ? this.api.getUser() : null;
        this.screenshotLoading = true;
        this.dashboardService.getScreenshots(this.chunksize, this.offset, this.setData.bind(this), user.id);
    }

    setData(result) {
        if (result.length > 0) {
            this.offset += this.chunksize;

            for (const block of result) {
                this.blocks.push(new ScreenshotsBlock(block));
            }
        } else {
            this.countFail += 1;
        }

        this.screenshotLoading = false;
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

    hour(datetime) {
        const regex = /(\d{4}-\d{2}-\d{2}) (\d{2}):\d{2}:\d{2}/;
        const matches = datetime.match(regex);

        return matches[2] + ':00 ' + matches[1];
    }

    minutes(datetime) {
        const regex = /\d{4}-\d{2}-\d{2} \d{2}:(\d{2}:\d{2})/;
        const matches = datetime.match(regex);

        return matches[1];
    }
}
