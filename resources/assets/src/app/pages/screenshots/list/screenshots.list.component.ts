import {Component, OnInit, OnDestroy} from '@angular/core';

import {ApiService} from '../../../api/api.service';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

import { LocalStorage } from '../../../api/storage.model';

import * as moment from 'moment';

@Component({
    selector: 'app-screenshots-list',
    templateUrl: './screenshots.list.component.html',
    styleUrls: ['./screenshots.list.component.scss',],
})
export class ScreenshotsListComponent implements OnInit, OnDestroy {
    userId: number[] = [];
    projectId: number[] = [];
    minDate: string = '';
    maxDate: string = '';

    _maxDate = moment.utc().add(1, 'day');

    protected _isManager?: boolean = null;
    get isManager(): boolean {
        if (this._isManager !== null) {
            return this._isManager;
        }

        const user = this.api.getUser();
        if (!user) {
            return this._isManager = false;
        }

        return this._isManager = this.allowedAction.can('screenshots/manager_access');
    }

    constructor(
        protected api: ApiService,
        protected allowedAction: AllowedActionsService,
    ) {
    }

    ngOnInit() {
        const filterByUser = LocalStorage.getStorage().get(`filterByUserIN${ window.location.pathname }`);
        if (filterByUser instanceof Array && filterByUser.length > 0) {
            this.userId = filterByUser;
        }

        const filterByProject = LocalStorage.getStorage().get(`filterByProjectIN${ window.location.pathname }`);
        if (filterByProject instanceof Array && filterByProject.length > 0) {
            this.projectId = filterByProject;
        }

        const filterByMaxDate = LocalStorage.getStorage().get(`filterByMaxDateIN${ window.location.pathname }`);
        if (filterByMaxDate) {
            this.maxDate = filterByMaxDate;
        }

        const filterByMinDate = LocalStorage.getStorage().get(`filterByMinDateIN${ window.location.pathname }`);
        if (filterByMinDate) {
            this.minDate = filterByMinDate;
        }
    }

    maxDateChanged(value) {
        LocalStorage.getStorage().set(`filterByMaxDateIN${ window.location.pathname }`, this.maxDate || '');
    }

    minDateChanged(value) {
        LocalStorage.getStorage().set(`filterByMinDateIN${ window.location.pathname }`, this.minDate || '');
    }

    cleanupParams() : string[] {
        return [
            'userId',
            'projectId',
            'minDate',
            'maxDate',
            '_maxDate',
            '_isManager',
            'api',
            'allowedAction',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
