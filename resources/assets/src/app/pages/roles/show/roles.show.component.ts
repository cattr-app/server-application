import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Role} from '../../../models/role.model';
import {RolesService} from '../roles.service';
import {AllowedActionsService} from '../allowed-actions.service';
import {ActivatedRoute} from '@angular/router';
import {ItemsShowComponent} from '../../items.show.component';
import {DualListComponent} from 'angular-dual-listbox';
import {RulesService} from '../rules.service';
import {UsersService} from '../../users/users.service';
import {User} from '../../../models/user.model';

@Component({
    selector: 'app-roles-show',
    templateUrl: './roles.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesShowComponent extends ItemsShowComponent implements OnInit {

    public item: Role = new Role();
    user: User;
    sourceRules: any = [];
    confirmedRules: any = [];
    sourceUsers: any = [];
    confirmedUsers: any = [];
    key = 'id';
    displayRules: any = 'name';
    displayUsers: any = 'full_name';
    keepSorted = true;
    filter = true;
    height = '250px';
    disabled = true;
    format: any = DualListComponent.DEFAULT_FORMAT;

    constructor(api: ApiService,
                roleService: RolesService,
                router: ActivatedRoute,
                allowService: AllowedActionsService,
                protected allowedService: AllowedActionsService,
                protected usersService: UsersService,
                protected ruleService: RulesService) {
        super(api, roleService, router, allowService);
    }

    ngOnInit() {
        super.ngOnInit();
        this.usersService.getItems(this.UsersUpdate.bind(this));
        this.ruleService.getActions(this.ActionsUpdate.bind(this));
        this.UserUpdate();
    }

    UserUpdate() {
        this.user = this.api.getUser();
    }

    can(action: string ): boolean {
        return this.allowedService.can(action);
    }

    editCallback(result) {
        console.log('Updated');
        console.log(result);
    }

    ActionsUpdate(result) {
        for (const item of result) {
            item['id'] = this.sourceRules.length;
            this.sourceRules.push(item);
        }
        this.allowedService.getItems(this.AllowedUpdate.bind(this), this.id);
    }

    AllowedUpdate(result) {
        this.confirmedRules = this.sourceRules.filter(function (action) {
            return result.some((item) => (item.object === action.object && item.action === action.action));
        });
    }

    UsersUpdate(result) {
        this.sourceUsers = result;
        const id = this.id;
        this.confirmedUsers = this.sourceUsers.filter(function (user) {
            return user.role_id === id;
        });
    }
}
