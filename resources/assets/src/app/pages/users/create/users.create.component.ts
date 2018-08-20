import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {User} from '../../../models/user.model';
import {Router} from '@angular/router';
import {UsersService} from '../users.service';
import {ItemsCreateComponent} from '../../items.create.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';


@Component({
    selector: 'app-users-create',
    templateUrl: './users.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersCreateComponent extends ItemsCreateComponent implements OnInit {

    public item: User = new User();

    constructor(api: ApiService,
                userService: UsersService,
                router: Router,
                allowedService: AllowedActionsService, ) {
        super(api, userService, router, allowedService);
    }

    ngOnInit() {
        super.ngOnInit();
        this.item.manual_time = 0;
        this.item.screenshots_interval = 500;
        this.item.timezone = '';
    }

    prepareData() {
        return {
            'full_name': this.item.full_name,
            'first_name': this.item.first_name,
            'last_name': this.item.last_name,
            'email': this.item.email,
            'avatar': this.item.avatar,
            'url': this.item.url,
            'active': this.item.active,
            'role_id': this.item.role_id,
            'screenshots_active': this.item.screenshots_active,
            'manual_time': this.item.manual_time,
            'screenshots_interval': this.item.screenshots_interval,
            'timezone': this.item.timezone,

            'password': this.item.password,

            /*'company_id': this.item.company_id,
            'level': this.item.level,
            'payroll_access': this.item.payroll_access,
            'billing_access': this.item.billing_access,
            'permanent_tasks': this.item.permanent_tasks,
            'computer_time_popup': this.item.computer_time_popup,
            'poor_time_popup': this.item.poor_time_popup,
            'blur_screenshots': this.item.blur_screenshots,
            'web_and_app_monitoring': this.item.web_and_app_monitoring,
            'webcam_shots': this.item.webcam_shots,
            'user_role_value': this.item.user_role_value,*/
        };
    }
}
