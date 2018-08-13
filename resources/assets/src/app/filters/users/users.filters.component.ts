import {Component, OnInit, Input, EventEmitter, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Router} from '@angular/router';
import { Location } from '@angular/common';
import {AttachedUsersService} from '../../pages/users/attached-users.service';

@Component({
    selector: 'app-users-filters',
    templateUrl: './users.filters.component.html',
})
export class UsersFiltersComponent implements OnInit {
    @Input() userId: any = null;
    @Output() userIdChange = new EventEmitter();
    user: any;
    users: Array<any> = [];
    isAuthorized = false;
    selectUsers: any = [];

    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
        protected attachedUsersService: AttachedUsersService,
    ) {
        this.isAuthorized = apiService.isAuthorized();
    }

    ngOnInit(): void {
        this.update(this.apiService.getUser());
        this.attachedUsersService.subscribeOnUpdate(this.onUserUpdate.bind(this));
        this.attachedUsersService.updateAttachedList();
    }

    update(user) {
        this.user = user;
    }

    onUserUpdate(users) {
        this.updateItems();
    }

    onChange($event) {
        if ($event.length > 0) {
            this.userId = $event.map(function(user) {
               return user.id;
            });
        } else {
            this.userId = null;
        }
        this.userIdChange.emit(this.userId);
    }

    updateItems(): void {
        if (!this.isAuthorized) {
            this.users = [];
            this.user = [];
            return;
        }
        const user: any = this.user;
        const attachedItems: any = this.attachedUsersService.attachedUsers;
        attachedItems.push(user);

        this.users = attachedItems;
    }

}
