import {Component, OnInit, OnDestroy} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Router, ActivatedRoute} from "@angular/router";
import {ScreenshotsService} from "../screenshots.service";
import {ItemsEditComponent} from "../../items.edit.component";
import {Screenshot} from "../../../models/screenshot.model";
import {AllowedActionsService} from "../../roles/allowed-actions.service";

@Component({
    selector: 'app-screenshots-edit',
    templateUrl: './screenshots.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ScreenshotsEditComponent extends ItemsEditComponent implements OnInit, OnDestroy {

    public item: Screenshot = new Screenshot();

    constructor(api: ApiService,
                screenshotService: ScreenshotsService,
                activatedRoute: ActivatedRoute,
                router: Router,
                allowedService: AllowedActionsService,) {
        super(api, screenshotService, activatedRoute, router, allowedService)
    }

    prepareData() {
        return {
            'time_interval_id': this.item.time_interval_id,
            'path': this.item.path,
            'important': this.item.important,
        }
    }

    cleanupParams() : string[] {
        return [
            'item',
            'api',
            'screenshotService',
            'activatedRoute',
            'router',
            'allowedService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
