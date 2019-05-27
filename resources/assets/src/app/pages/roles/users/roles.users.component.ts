import {Component, OnInit, OnDestroy, IterableDiffers} from '@angular/core';
import {Router, ActivatedRoute} from '@angular/router';

import {Role} from '../../../models/role.model';
import {User} from '../../../models/user.model';

import {ItemsEditComponent} from '../../items.edit.component';
import {DualListComponent} from 'angular-dual-listbox';

import {ApiService} from '../../../api/api.service';
import {RolesService} from '../roles.service';
import {UsersService} from '../../users/users.service';
import {AllowedActionsService} from '../allowed-actions.service';
import {TranslateService} from '@ngx-translate/core';


@Component({
    selector: 'app-roles-users',
    templateUrl: './roles.users.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesUsersComponent extends ItemsEditComponent implements OnInit, OnDestroy {

    public item: Role = new Role();
    user: User;
    sourceUsers: any = [];
    confirmedUsers: any = [];
    initialConfirmedUsers: any = [];
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
                translate: TranslateService,
                differs: IterableDiffers) {
        super(api, roleService, activatedRoute, router, allowedService);
        this.differUsers = differs.find([]).create(null);


        translate.get('control.add').subscribe((res: string) => { this.format.add = res});
        translate.get('control.remove').subscribe((res: string) => { this.format.remove = res});
        translate.get('control.all').subscribe((res: string) => { this.format.all = res});
        translate.get('control.none').subscribe((res: string) => { this.format.none = res});
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

    changeUsers() {
      const confirmedUsers = Array.from(new Set([...this.confirmedUsers, ...this.initialConfirmedUsers]));
      if (confirmedUsers.length !== this.confirmedUsers.length) {
        this.msgs.push({severity: 'error', summary: 'Error', detail: "Can't remove role. Use user edit page to change role"});
      }

      this.confirmedUsers = confirmedUsers;
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
            return +user.role_id === +id;
        });
        this.initialConfirmedUsers = [...this.confirmedUsers];
        this.differUsers.diff(this.confirmedUsers);
    }


    cleanupParams() : string[] {
        return [
            'item',
            'user',
            'sourceUsers',
            'confirmedUsers',
            'key',
            'displayUsers',
            'keepSorted',
            'filter',
            'height',
            'format',
            'differUsers',
            'api',
            'roleService',
            'activatedRoute',
            'router',
            'allowedService',
            'usersService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
