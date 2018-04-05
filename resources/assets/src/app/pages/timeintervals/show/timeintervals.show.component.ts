import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {TimeIntervalsService} from "../timeintervals.service";
import {TimeInterval} from "../../../models/timeinterval.model";
import {ItemsShowComponent} from "../../items.show.component";
import {ScreenshotsService} from "../../screenshots/screenshots.service";
import {Screenshot} from "../../../models/screenshot.model";

@Component({
    selector: 'app-timeintervals-show',
    templateUrl: './timeintervals.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TimeIntervalsShowComponent extends ItemsShowComponent implements OnInit {

    public item: TimeInterval = new TimeInterval();
    public screenshot: Screenshot = new Screenshot();

    constructor(protected api: ApiService,
                protected timeIntervalService: TimeIntervalsService,
                protected screenshotsService: ScreenshotsService,
                protected router: ActivatedRoute) {
        super(api, timeIntervalService, router);
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.timeIntervalService.getItem(this.id, this.setItem.bind(this));
        this.screenshotsService.getScreenshotByIntervalId(this.id, this.setScreenshot.bind(this))
    }

    setScreenshot(result) {
        this.screenshot = result;
    }

    isEmptyObject(obj) {
        return (obj && (Object.keys(obj).length === 0));
    }
}
