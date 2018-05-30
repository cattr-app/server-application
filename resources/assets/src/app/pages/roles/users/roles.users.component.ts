import {Component, OnInit, IterableDiffers} from '@angular/core';
import {Router, ActivatedRoute} from '@angular/router';

import {Role} from '../../../models/role.model';
import {User} from '../../../models/user.model';

import {ItemsEditComponent} from '../../items.edit.component';
import {DualListComponent} from 'angular-dual-listbox';

import {ApiService} from '../../../api/api.service';
import {RolesService} from '../roles.service';
import {UsersService} from '../../users/users.service';
import {AllowedActionsService} from '../allowed-actions.service';


@Component({
    selector: 'app-roles-users',
    templateUrl: './roles.users.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesUsersComponent extends ItemsEditComponent implements OnInit {

    public item: Role = new Role();
    user: User;
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
                roleService: RolesService,
                activatedRoute: ActivatedRoute,
                router: Router,
                protected allowedService: AllowedActionsService,
                protected usersService: UsersService,
                differs: IterableDiffers) {
        super(api, roleService, activatedRoute, router, allowedService);
        this.differUsers = differs.find([]).create(null);
    }

    prepareData() {
        return {
            'name': this.item.name,
        };
    }

    ngOnInit() {
        super.ngOnInit();
        this.usersService.getItems(this.UsersUpdate.bind(this));
    }

    onSubmit() {
        const id = this.id;
        const users = [];
        const UserChanges = this.differUsers.diff(this.confirmedUsers);

        if (UserChanges) {
            UserChanges.forEachAddedItem((record) => {
                record.item.role_id = id;
                users.push(record.item);
            });
            UserChanges.forEachRemovedItem((record) => {
                record.item.role_id = null;
                users.push(record.item);
            });
        }

        if (users.length > 0) {
            this.usersService.editItems(users, this.editBulkCallback.bind(this, 'Users'));
        }
    }

    editBulkCallback(name, results) {
        const errors = [];
        for (const msg of results.messages) {
            if (msg.error) {
                let reason = '';
                if (Object.keys(msg.reason).length > 0) {
                    Object.keys(msg.reason).forEach(function (element, index) {
                        reason += ' ' + msg.reason[element][0];
                    });
                } else {
                    reason = msg.reason;
                }
                errors.push({'error': msg.error, 'reason': reason});
            }
        }

        if (errors.length > 0) {
            for (const err of errors) {
                this.msgs.push({severity: 'error', summary: name + ' ' + err.error, detail: err.reason});
            }
        } else {
            this.msgs.push({severity: 'success', summary: 'Success', detail:  name + ' has been updated'});
        }
    }

    errorCallback(result) {
        this.msgs.push({severity: 'error', summary: result.error.error, detail: result.error.reason});
    }

    UsersUpdate(result) {
        this.sourceUsers = result;
        const id = this.id;
        this.confirmedUsers = this.sourceUsers.filter(function (user) {
            return user.role_id === id;
        });
        this.differUsers.diff(this.confirmedUsers);
    }
}
