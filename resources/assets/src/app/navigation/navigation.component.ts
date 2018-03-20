import {Component, OnInit} from '@angular/core';
import {ApiService} from "../api/api.service";
import {AllowedActionsService} from "../pages/roles/allowed-actions.service";
import {AllowedAction} from "../models/allowed-action.model";
import {Router} from "@angular/router";
import { Location } from '@angular/common';

interface NavigationLink {
    title: string;
    action: string;
    isLink: boolean;
}

@Component({
    selector: 'app-navigation',
    templateUrl: './navigation.component.html',
})
export class NavigationComponent implements OnInit {
    items: NavigationLink[];
    allowedActions: AllowedAction[] = [];
    isAuthorized: boolean = false;

    protected itemsGuest: NavigationLink[] = [
        {title: "Login", action: "auth/login", isLink: true},
        {title: "Forgot", action: "auth/forgot", isLink: true},
        {title: "Reset", action: "auth/reset", isLink: true},
        {title: "Register", action: "auth/register", isLink: true},
    ];

    protected itemsAuthorized: NavigationLink[] = [
        {title: "Dashboard", action: "dashboard", isLink: true},
        {title: "Projects", action: "projects/list", isLink: true},
        {title: "Tasks", action: "tasks/list", isLink: true},
        {title: "Users", action: "users/list", isLink: true},
        {title: "Screenshots", action: "screenshots/list", isLink: true},
        {title: "Time Intervals", action: "timeintervals/list", isLink: true},
        {title: "Role", action: "roles/list", isLink: true},
        {title: "Logout", action: "onLogout", isLink: false},
    ];

    ngOnInit(): void {
        this.updateItems();
        this.updateAllowedList();
    }

    updateAllowedList() {
        this.allowedService.getItems(this.setupAllowedList.bind(this));
    }

    setupAllowedList(items) {
        this.allowedActions = items;
        this.updateItems();
    }

    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
        protected allowedService: AllowedActionsService,

    ) {
        this.isAuthorized = apiService.isAuthorized();
        apiService.auth.subscribe(this.setAuth.bind(this));
    }

    setAuth(status: boolean): void {
        this.isAuthorized = status;
        this.updateItems();
        this.updateAllowedList();
    }

    updateItems(): void {

        if(!this.isAuthorized) {
            this.items = this.itemsGuest;
            return;
        }


        let allowedItems: NavigationLink[] = [];

        for(let item of this.itemsAuthorized) {

            if(!item.isLink) {
                allowedItems.push(item);
                continue;
            }

            if(!this.allowedActions.length) {
                continue;
            }

            for(let allowedAction of this.allowedActions) {

                if(allowedAction.object + '/' + allowedAction.action ==
                    item.action) {
                    allowedItems.push(item);
                    break;
                }
            }

        }

        this.items = allowedItems;
    }

    processLinkAction(action): any {
        const callback = this[action];
        const type = typeof callback;

        if (type !== "function") {
            throw new Error(`Unknown method NavigationComponent.${action}`);
        }

        return callback.bind(this)();
    }

    onLogout(): false {
        this.apiService.setToken(null);
        this.location.replaceState("/");
        this.router.navigateByUrl("/");

        return false;
    }
}
