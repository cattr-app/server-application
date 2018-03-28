import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {User} from "../../../models/user.model";
import {Router, ActivatedRoute} from "@angular/router";
import {UsersService} from "../users.service";
import {ItemsEditComponent} from "../../items.edit.component";
import {AllowedActionsService} from "../../roles/allowed-actions.service";

@Component({
    selector: 'app-users-edit',
    templateUrl: './users.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersEditComponent extends ItemsEditComponent implements OnInit {

    public item: User = new User();

    constructor(api: ApiService,
                userService: UsersService,
                activatedRoute: ActivatedRoute,
                router: Router,
                allowedService: AllowedActionsService,) {
        super(api, userService, activatedRoute, router, allowedService)
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
