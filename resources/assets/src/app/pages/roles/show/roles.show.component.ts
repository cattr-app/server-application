import {Component, OnInit, OnDestroy} from '@angular/core';
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
import {TranslateService} from '@ngx-translate/core';

@Component({
    selector: 'app-roles-show',
    templateUrl: './roles.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesShowComponent extends ItemsShowComponent implements OnInit, OnDestroy {

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
                translate: TranslateService,
                protected usersService: UsersService,
                protected ruleService: RulesService) {
        super(api, roleService, router, allowService);

        translate.get('control.add').subscribe((res: string) => { this.format.add = res});
        translate.get('control.remove').subscribe((res: string) => { this.format.remove = res});
        translate.get('control.all').subscribe((res: string) => { this.format.all = res});
        translate.get('control.none').subscribe((res: string) => { this.format.none = res});
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
        return this.allowedAction.can(action);
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
        this.allowedAction.getItems(this.AllowedUpdate.bind(this), this.id);
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


    cleanupParams() : string[] {
        return [
            'item',
            'user',
            'sourceRules',
            'confirmedRules',
            'sourceUsers',
            'confirmedUsers',
            'key',
            'displayRules',
            'displayUsers',
            'keepSorted',
            'filter',
            'height',
            'disabled',
            'format',
            'api',
            'roleService',
            'router',
            'allowService',
            'allowedService',
            'usersService',
            'ruleService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
