import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {User} from "../../../models/user.model";
import {Router} from "@angular/router";
import {UsersService} from "../users.service";


@Component({
    selector: 'app-users-create',
    templateUrl: './users.create.component.html',
    styleUrls: ['./users.create.component.scss']
})
export class UsersCreateComponent implements OnInit {

    public user: User = new User();

    constructor(private api: ApiService,
                private userService: UsersService,
                private router: Router) {
    }

    ngOnInit() {

    }

    public onSubmit() {
        this.userService.createItem(
            this.prepareData(),
            this.createCallback.bind(this)
        );
    }

    prepareData() {
        return {
            'full_name': this.user.first_name + this.user.last_name,
            'first_name': this.user.first_name,
            'last_name': this.user.last_name,
            'email': this.user.email,
            'url': this.user.url,
            'company_id': this.user.company_id,
            'level': this.user.level,
            'payroll_access': this.user.payroll_access,
            'billing_access': this.user.billing_access,
            'avatar': this.user.avatar,
            'screenshots_active': this.user.screenshots_active,
            'manual_time': this.user.manual_time,
            'permanent_tasks': this.user.permanent_tasks,
            'computer_time_popup': this.user.computer_time_popup,
            'poor_time_popup': this.user.poor_time_popup,
            'blur_screenshots': this.user.blur_screenshots,
            'web_and_app_monitoring': this.user.web_and_app_monitoring,
            'webcam_shots': this.user.webcam_shots,
            'screenshots_interval': this.user.screenshots_interval,
            'user_role_value': this.user.user_role_value,
            'active': this.user.active,
            'password': this.user.password,
        }
    }

    createCallback(result) {
        console.log(result);
        this.router.navigateByUrl('/users/list');
    }
}
