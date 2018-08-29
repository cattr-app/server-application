import {Component} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import { AllowedActionsService } from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-screenshots-list',
    templateUrl: './screenshots.list.component.html',
    styleUrls: []
})
export class ScreenshotsListComponent {
    userId: number = null;
    projectId: number = null;

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
}
