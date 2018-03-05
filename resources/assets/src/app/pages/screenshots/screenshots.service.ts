import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {ItemsService} from "../items.service";
import {Screenshot} from "../../models/screenshot.model";


@Injectable()
export class ScreenshotsService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'screenshots';
    }

    convertFromApi(itemFromApi) {
        return new Screenshot(itemFromApi);
    }
}
