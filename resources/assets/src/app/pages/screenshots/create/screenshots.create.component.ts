import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Router} from "@angular/router";
import {ScreenshotsService} from "../screenshots.service";
import {ItemsCreateComponent} from "../../items.create.component";
import {Screenshot} from "../../../models/screenshot.model";

@Component({
    selector: 'app-screenshots-create',
    templateUrl: './screenshots.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ScreenshotsCreateComponent extends ItemsCreateComponent implements OnInit {

    public item: Screenshot = new Screenshot();

    constructor(api: ApiService,
                screenshotService: ScreenshotsService,
                router: Router) {
        super(api, screenshotService, router);
    }

    prepareData() {
        return {
            'time_interval_id': this.item.time_interval_id,
            'name': this.item.name,
            'path': this.item.path
        }
    }
}
