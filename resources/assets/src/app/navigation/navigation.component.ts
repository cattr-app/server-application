import {Component, OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {AllowedActionsService} from '../pages/roles/allowed-actions.service';
import {Router} from '@angular/router';
import { Location } from '@angular/common';

interface NavigationLink {
    title: string;
    action: string;
    isLink: boolean;
    permissions?: string[];
}

@Component({
    selector: 'app-navigation',
    templateUrl: './navigation.component.html',
})
export class NavigationComponent implements OnInit {
    items: NavigationLink[];
    isAuthorized = false;

    protected itemsGuest: NavigationLink[] = [
        {title: 'Login', action: 'auth/login', isLink: true},
        {title: 'Forgot', action: 'auth/forgot', isLink: true},
        {title: 'Reset', action: 'auth/reset', isLink: true},
        {title: 'Register', action: 'auth/register', isLink: true},
    ];

    protected itemsAuthorized: NavigationLink[] = [
        {title: 'Dashboard', action: 'dashboard', isLink: true, permissions: [ 'dashboard' ] },
        {title: 'Projects', action: 'projects/list', isLink: true, permissions: [ 'projects/list' ]},
        {title: 'Tasks', action: 'tasks/list', isLink: true, permissions: [ 'tasks/list' ]},
        {title: 'Users', action: 'users/list', isLink: true, permissions: [ 'users/list' ]},
        {title: 'Screenshots', action: 'screenshots/list', isLink: true, permissions: [ 'screenshots/list' ]},
        {title: 'Time Intervals', action: 'time-intervals/list', isLink: true, permissions: [ 'time-intervals/list' ]},
        {title: 'Statistic', action: 'statistic/time', isLink: true, permissions: [ 'users/list', 'time-intervals/list'  ]},
        {title: 'Integrations', action: 'integrations', isLink: true, permissions: [ 'projects/list' ]},
        {title: 'Role', action: 'roles/list', isLink: true, permissions: [ 'roles/list' ]},
        {title: 'Logout', action: 'onLogout', isLink: false},
    ];

    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
        protected allowedService: AllowedActionsService,
    ) {
        this.isAuthorized = apiService.isAuthorized();
        apiService.auth.subscribe(this.setAuth.bind(this));
    }

    ngOnInit(): void {
        this.updateItems();
        this.allowedService.subscribeOnUpdate(this.onAllowedActionsUpdate.bind(this));

        if (this.isAuthorized) {
            this.allowedService.updateAllowedList();
        }
    }

    onAllowedActionsUpdate(items) {
        this.updateItems();
    }

    setAuth(status: boolean): void {
        this.isAuthorized = status;

        if (status) {
            this.allowedService.updateAllowedList();
        } else {
            this.updateItems();
        }
    }

    updateItems(): void {
        if (!this.isAuthorized) {
            this.items = this.itemsGuest;
            return;
        }

        const allowedItems: NavigationLink[] = [];

        for (const item of this.itemsAuthorized) {
            if (!item.isLink) {
                allowedItems.push(item);
                continue;
            }


            if (!item.permissions || !item.permissions.length) {
                allowedItems.push(item);
                continue;
            }

            let allow : boolean = true;
            for (const APIaction of item.permissions) {
                if (!this.allowedService.can(APIaction)) {
                    allow = false;
                    break;
                }
            }

            if (allow) {
                allowedItems.push(item);
            }
        }

        this.items = allowedItems;
    }

    processLinkAction(action): any {
        const callback = this[action];
        const type = typeof callback;

        if (type !== 'function') {
            throw new Error(`Unknown method NavigationComponent.${action}`);
        }

        return callback.bind(this)();
    }

    onLogout(): false {
        const callback = function(response) {
            console.log(response);
            this.apiService.setToken(null);
            this.apiService.setAttachedUsers(null);
            this.apiService.setAttachedProjects(null);
            this.apiService.setAllowedActions(null);
            this.location.replaceState('/');
            this.router.navigateByUrl('/');
        };
        this.apiService.logout(callback.bind(this));

        return false;
    }
}
