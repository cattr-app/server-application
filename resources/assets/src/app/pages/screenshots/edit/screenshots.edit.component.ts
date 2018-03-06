import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {ScreenshotsService} from "../screenshots.service";
import {ItemsEditComponent} from "../../items.edit.component";
import {Screenshot} from "../../../models/screenshot.model";

@Component({
    selector: 'app-screenshots-edit',
    templateUrl: './screenshots.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ScreenshotsEditComponent extends ItemsEditComponent implements OnInit {

    public item: Screenshot = new Screenshot();

    constructor(api: ApiService,
                screenshotService: ScreenshotsService,
                router: ActivatedRoute) {
        super(api, screenshotService, router)
    }

    prepareData() {
        return {
            'time_interval_id': this.item.time_interval_id,
            'path': this.item.path
        }
    }
}
