import {Component, OnInit} from '@angular/core';
import {ApiService} from "../api/api.service";
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
        {title: "Integrations", action: "integrations", isLink: true},
        {title: "Logout", action: "onLogout", isLink: false},
    ];

    ngOnInit(): void {
        this.updateItems();
    }

    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location
    ) {
        this.isAuthorized = apiService.isAuthorized();
        apiService.auth.subscribe(this.setAuth.bind(this));
    }

    setAuth(status: boolean): void {
        this.isAuthorized = status;
        this.updateItems();
    }

    updateItems(): void {
        this.items = this.isAuthorized ? this.itemsAuthorized : this.itemsGuest;
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
