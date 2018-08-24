import {Component} from '@angular/core';
import {ApiService} from '../../../api/api.service';

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

        const managerRoles = [1, 5];
        return this._isManager = managerRoles.includes(user.role_id);
    }

    constructor(protected api: ApiService,
    ) {
    }
}
