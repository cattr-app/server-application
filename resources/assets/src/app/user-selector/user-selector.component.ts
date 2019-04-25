import { Component, OnInit, OnDestroy, Output, EventEmitter, Input } from '@angular/core';

import { UsersService } from '../pages/users/users.service';

import { User } from '../models/user.model';
import { LocalStorage } from '../api/storage.model';

interface UserSelectItem {
    id: number;
    full_name: string;
}

@Component({
    selector: 'app-user-selector',
    templateUrl: './user-selector.component.html',
    styleUrls: ['./user-selector.component.scss']
})
export class UserSelectorComponent implements OnInit, OnDestroy {
    isLoading: boolean = true;
    usersAvailable: UserSelectItem[] = [];
    usersSelected: UserSelectItem[] = [];

    @Input() filter = (user: User) => true;

    @Output() added = new EventEmitter<UserSelectItem>();
    @Output() removed = new EventEmitter<UserSelectItem>();
    @Output() changed = new EventEmitter<UserSelectItem[]>();

    constructor(
        protected userService: UsersService,
    ) { }

    fetchUsers() {
        return new Promise<User[]>(resolve => {
            this.isLoading = true;

            this.userService.getItems((users: User[]) => {
                users = users
                    .filter(this.filter.bind(this))
                    .sort((a, b) => a.full_name.localeCompare(b.full_name));

                // Add 'Select all' item.
                this.usersAvailable = [{ id: 0, full_name: 'Select all' }, ...users];

                this.isLoading = false;
                resolve(users);
            });
        });
    }

    ngOnInit() {
        this.fetchUsers().then(users => {
            const savedUsersIds = LocalStorage.getStorage().get(`filterByUserIN${window.location.pathname}`);
            if (savedUsersIds) {
                this.usersSelected = users.filter(user => savedUsersIds.includes(+user.id));
            } else {
                this.usersSelected = users;
            }
            this.usersSelected = this.usersSelected.filter(this.filter.bind(this));
            this.changed.emit(this.usersSelected);
        });
    }

    add(user: UserSelectItem) {
        if (+user.id !== 0) {
            // Not emit event for the 'Select all' item.
            this.added.emit(user);
        }
    }

    remove(user: UserSelectItem) {
        if (+user.id !== 0) {
            // Not emit event for the 'Select all' item.
            this.removed.emit(user);
        }
    }

    change(users: UserSelectItem[]) {
        if (!users.find(user => +user.id === 0)) {
            this.changed.emit(users);

            const userIds = this.usersSelected.map(user => +user.id);
            LocalStorage.getStorage().set(`filterByUserIN${window.location.pathname}`, userIds);
        } else {
            // Handle 'Select all'.
            this.usersSelected = this.usersAvailable.filter(user => +user.id !== 0);
            this.changed.emit(this.usersSelected);

            const userIds = this.usersSelected.map(user => +user.id);
            LocalStorage.getStorage().set(`filterByUserIN${window.location.pathname}`, userIds);
        }
    }

    cleanupParams() : string[] {
        return [
            'isLoading',
            'usersAvailable',
            'usersSelected',
            'filter',
            'added',
            'removed',
            'changed',
            'userService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
