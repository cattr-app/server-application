import {Component, OnInit, IterableDiffers} from '@angular/core';
import {Router, ActivatedRoute} from '@angular/router';

import {User} from '../../../models/user.model';

import {ItemsEditComponent} from '../../items.edit.component';
import {DualListComponent} from 'angular-dual-listbox';

import {ApiService} from '../../../api/api.service';
import {UsersService} from '../users.service';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-users-attached-users',
    templateUrl: './users.attached-users.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersAttachedUsersComponent extends ItemsEditComponent implements OnInit {

    public item: User = new User();
    sourceUsers: any = [];
    confirmedUsers: any = [];
    key = 'id';
    displayUsers: any = 'full_name';
    keepSorted = true;
    filter = true;
    height = '250px';
    format: any = DualListComponent.DEFAULT_FORMAT;
    differUsers: any;

    constructor(api: ApiService,
                activatedRoute: ActivatedRoute,
                router: Router,
                protected allowedService: AllowedActionsService,
                protected usersService: UsersService,
                differs: IterableDiffers) {
        super(api, usersService, activatedRoute, router, allowedService);
        this.differUsers = differs.find([]).create(null);
    }

    prepareData() {
        return {
            'name': this.item.full_name,
        };
    }

    ngOnInit() {
        this.sub = this.activatedRoute.params.subscribe(params => {
            this.id = +params['id'];
        });
        this.itemService.getItem(this.id, this.setItem.bind(this), {'with': 'attached_users'});
        this.usersService.getItems(this.UsersUpdate.bind(this));
    }

    setItem(result) {
        this.item = result;
        this.confirmedUsers = this.item.attached_users;
        this.differUsers.diff(this.confirmedUsers);
    }

    onSubmit() {
        const addUsers = [];
        const removeUsers = [];
        const UserChanges = this.differUsers.diff(this.confirmedUsers);

        if (UserChanges) {
            UserChanges.forEachAddedItem((record) => {
                addUsers.push(
                    {
                        'attached_user_id': record.item.id,
                        'user_id': this.id
                    },
                );
            });
            UserChanges.forEachRemovedItem((record) => {
                removeUsers.push(
                    {
                        'attached_user_id': record.item.id,
                        'user_id': this.id
                    },
                );
            });
        }

        if (addUsers.length > 0) {
            this.usersService.createAssignedUsers(addUsers, this.editBulkCallback.bind(this, 'Users'));
        }

        if (removeUsers.length > 0) {
            this.usersService.removeAssignedUsers(removeUsers, this.editBulkCallback.bind(this, 'Users'));
        }
    }

    errorCallback(result) {
        this.msgs.push({severity: 'error', summary: result.error.error, detail: result.error.reason});
    }

    UsersUpdate(result) {
        this.sourceUsers = result;
    }
}
