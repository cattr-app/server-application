import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {User} from "../../models/user.model";
import {ItemsService} from "../items.service";

@Injectable()
export class UsersService extends ItemsService {

    getApiPath() {
        return 'users';
    }

    constructor(api: ApiService) {
        super(api);
    }

    convertFromApi(itemFromApi) {
        return new User(
            itemFromApi.id,
            itemFromApi.full_name,
            itemFromApi.first_name,
            itemFromApi.last_name,
            itemFromApi.email,
            itemFromApi.url,
            itemFromApi.company_id,
            itemFromApi.level,
            itemFromApi.payroll_access,
            itemFromApi.billing_access,
            itemFromApi.avatar,
            itemFromApi.screenshots_active,
            itemFromApi.manual_time,
            itemFromApi.permanent_tasks,
            itemFromApi.computer_time_popup,
            itemFromApi.poor_time_popup,
            itemFromApi.blur_screenshots,
            itemFromApi.web_and_app_monitoring,
            itemFromApi.webcam_shots,
            itemFromApi.screenshots_interval,
            itemFromApi.user_role_value,
            itemFromApi.active,
            itemFromApi.password,
            itemFromApi.role_id,
            itemFromApi.created_at,
            itemFromApi.updated_at,
            itemFromApi.deleted_at
        )
    }
}
