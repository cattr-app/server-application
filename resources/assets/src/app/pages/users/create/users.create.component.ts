import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {User} from '../../../models/user.model';
import {Router} from '@angular/router';
import {UsersService} from '../users.service';
import {ItemsCreateComponent} from '../../items.create.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import { RolesService } from '../../roles/roles.service';
import { Role } from '../../../models/role.model';

@Component({
    selector: 'app-users-create',
    templateUrl: './users.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersCreateComponent extends ItemsCreateComponent implements OnInit {
    public item: User = new User();
    public roles: Role[] = [];

    constructor(api: ApiService,
                userService: UsersService,
                router: Router,
                allowedService: AllowedActionsService,
                protected rolesService: RolesService) {
        super(api, userService, router, allowedService);
    }

    ngOnInit() {
        super.ngOnInit();
        this.item.manual_time = 0;
        this.item.screenshots_interval = 5;
        this.item.computer_time_popup = 5;
        this.item.timezone = '';
        this.rolesService.getItems(items => {
            this.roles = items;
        });
    }

    prepareData() {
        return {
            'full_name': this.item.full_name,
            'first_name': this.item.first_name,
            'last_name': this.item.last_name,
            'email': this.item.email,
            'active': this.item.active,
            'role_id': this.item.role_id,
            'screenshots_active': this.item.screenshots_active,
            'manual_time': this.item.manual_time,
            'screenshots_interval': this.item.screenshots_interval,
            "computer_time_popup": this.item.computer_time_popup,
            'timezone': this.item.timezone,
            'password': this.item.password,
        };
    }
}
