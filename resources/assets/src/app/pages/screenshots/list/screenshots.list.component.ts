import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { LocalStorage } from '../../../api/storage.model';

@Component({
    selector: 'app-screenshots-list',
    templateUrl: './screenshots.list.component.html',
    styleUrls: []
})
export class ScreenshotsListComponent implements OnInit {
    userId: number[] = [];
    projectId: number[] = [];

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
    }
}
