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
        this.userService.createUser(
            this.user.first_name +  this.user.last_name,
            this.user.first_name,
            this.user.last_name,
            this.user.email,
            this.user.url,
            this.user.company_id,
            this.user.level,
            this.user.payroll_access,
            this.user.billing_access,
            this.user.avatar,
            this.user.screenshots_active,
            this.user.manual_time,
            this.user.permanent_tasks,
            this.user.computer_time_popup,
            this.user.poor_time_popup,
            this.user.blur_screenshots,
            this.user.web_and_app_monitoring,
            this.user.webcam_shots,
            this.user.screenshots_interval,
            this.user.user_role_value,
            this.user.active,
            this.user.password,
            this.createCallback.bind(this)
        );
    }

    createCallback(result) {
        console.log(result);
        this.router.navigateByUrl('/users/list');
    }
}
