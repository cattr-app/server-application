import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {ItemsService} from "../items.service";
import {Screenshot} from "../../models/screenshot.model";
import {Item} from "../../models/item.model";


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

    getScreenshotByIntervalId(intervalId, callback) {
        let screenshot: Screenshot;

        return this.api.send(
            this.getApiPath() + '/get',
            {'interval_id': intervalId},
            (taskFromApi) => {
                screenshot = this.convertFromApi(taskFromApi);

                callback(screenshot);
            });
    }

    createItem(data, callback) {
        this.api.sendFile(
            this.getApiPath() + '/create',
            data,
            (result) => {
                callback(result);
            }
        );
    }
}
