import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from "../../items.list.component";
import {Screenshot} from "../../../models/screenshot.model";
import {ScreenshotsService} from "../screenshots.service";

@Component({
    selector: 'app-screenshots-list',
    templateUrl: './screenshots.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ScreenshotsListComponent extends ItemsListComponent implements OnInit {

    itemsArray: Screenshot[] = [];
    p: number = 1;

    constructor(api: ApiService,
                screenshotService: ScreenshotsService,
                modalService: BsModalService,) {
        super(api, screenshotService, modalService);
    }
}
