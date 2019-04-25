import {Component, OnInit, OnDestroy, ViewChild} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Router} from "@angular/router";
import {ScreenshotsService} from "../screenshots.service";
import {ItemsCreateComponent} from "../../items.create.component";
import {Screenshot} from "../../../models/screenshot.model";
import {AllowedActionsService} from "../../roles/allowed-actions.service";

@Component({
    selector: 'app-screenshots-create',
    templateUrl: './screenshots.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ScreenshotsCreateComponent extends ItemsCreateComponent implements OnInit, OnDestroy {
    @ViewChild("fileInput") fileInput;

    public item: Screenshot = new Screenshot();
    private screenshotService: any;

    constructor(api: ApiService,
                screenshotService: ScreenshotsService,
                router: Router,
                allowedService: AllowedActionsService,) {
        super(api, screenshotService, router, allowedService);
        this.screenshotService = screenshotService;
    }

    prepareData() {
        let fi = this.fileInput.nativeElement;
        let fileToUpload = fi.files[0];

        let formData = new FormData();
        formData.append('screenshot', fileToUpload, fileToUpload.name);
        formData.append('time_interval_id', this.item.time_interval_id.toString());

        return formData;
    }


    cleanupParams() : string[] {
        return [
            'fileInput',
            'item',
            'screenshotService',
            'api',
            'screenshotService',
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
