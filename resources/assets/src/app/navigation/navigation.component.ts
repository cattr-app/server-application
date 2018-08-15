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
        {title: 'navigation.login', action: 'auth/login', isLink: true},
        {title: 'navigation.forgot', action: 'auth/forgot', isLink: true},
        {title: 'navigation.reset', action: 'auth/reset', isLink: true},
        {title: 'navigation.register', action: 'auth/register', isLink: true},
    ];

    protected itemsAuthorized: NavigationLink[] = [
        {title: 'navigation.dashboard', action: 'dashboard', isLink: true, permissions: [ 'dashboard' ] },
        {title: 'navigation.projects', action: 'projects/list', isLink: true, permissions: [ 'projects/list' ]},
        {title: 'navigation.tasks', action: 'tasks/list', isLink: true, permissions: [ 'tasks/list' ]},
        {title: 'navigation.users', action: 'users/list', isLink: true, permissions: [ 'users/list' ]},
        {title: 'navigation.screenshots', action: 'screenshots/list', isLink: true, permissions: [ 'screenshots/list' ]},
        {title: 'navigation.statistic', action: 'statistic/time', isLink: true, permissions: [ 'users/list', 'time-intervals/list'  ]},
        {title: 'navigation.integrations', action: 'integrations', isLink: true, permissions: [ 'projects/list' ]},
        {title: 'navigation.role', action: 'roles/list', isLink: true, permissions: [ 'roles/list' ]},
        {title: 'navigation.settings', action: 'settings', isLink: true},
        {title: 'navigation.logout', action: 'onLogout', isLink: false},
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
