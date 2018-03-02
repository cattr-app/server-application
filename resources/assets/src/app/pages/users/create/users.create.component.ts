import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {User} from "../../../models/user.model";
import {Router} from "@angular/router";
import {UsersService} from "../users.service";
import {ItemsCreateComponent} from "../../items.create.component";


@Component({
    selector: 'app-users-create',
    templateUrl: './users.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersCreateComponent extends ItemsCreateComponent implements OnInit {

    public item: User = new User();

    constructor(api: ApiService,
                userService: UsersService,
                router: Router) {
        super(api, userService, router);
    }

    prepareData() {
        return {
            'full_name': this.item.first_name + this.item.last_name,
            'first_name': this.item.first_name,
            'last_name': this.item.last_name,
            'email': this.item.email,
            'url': this.item.url,
            'company_id': this.item.company_id,
            'level': this.item.level,
            'payroll_access': this.item.payroll_access,
            'billing_access': this.item.billing_access,
            'avatar': this.item.avatar,
            'screenshots_active': this.item.screenshots_active,
            'manual_time': this.item.manual_time,
            'permanent_tasks': this.item.permanent_tasks,
            'computer_time_popup': this.item.computer_time_popup,
            'poor_time_popup': this.item.poor_time_popup,
            'blur_screenshots': this.item.blur_screenshots,
            'web_and_app_monitoring': this.item.web_and_app_monitoring,
            'webcam_shots': this.item.webcam_shots,
            'screenshots_interval': this.item.screenshots_interval,
            'user_role_value': this.item.user_role_value,
            'active': this.item.active,
            'password': this.item.password,
        }
    }
}
