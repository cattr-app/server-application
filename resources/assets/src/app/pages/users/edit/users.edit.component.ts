import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {User} from "../../../models/user.model";
import {ActivatedRoute} from "@angular/router";
import {UsersService} from "../users.service";


@Component({
    selector: 'app-users-edit',
    templateUrl: './users.edit.component.html',
    styleUrls: ['./users.edit.component.scss']
})
export class UsersEditComponent implements OnInit {
    id: number;
    private sub: any;
    public user: User = new User();

    constructor(private api: ApiService,
                private userService: UsersService,
                private router: ActivatedRoute) {
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.userService.getUser(this.id, this.setTask.bind(this));
    }

    public onSubmit() {
        this.userService.editUser(
            this.id,
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
            this.editCallback.bind(this)
        );
    }

    setTask(result) {
        console.log(result);
        this.user = result;
    }

    editCallback(result) {
        console.log("Updated");
    }
}
