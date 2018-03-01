import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {User} from "../../models/user.model";

@Injectable()
export class UsersService {

    constructor(private api: ApiService) {
    }

    createUser(full_name,
               first_name,
               last_name,
               email,
               url,
               company_id,
               level,
               payroll_access,
               billing_access,
               avatar,
               screenshots_active,
               manual_time,
               permanent_tasks,
               computer_time_popup,
               poor_time_popup,
               blur_screenshots,
               web_and_app_monitoring,
               webcam_shots,
               screenshots_interval,
               user_role_value,
               active,
               password,
               callback) {
        this.api.send(
            'users/create',
            {
                'full_name': full_name,
                'first_name': first_name,
                'last_name': last_name,
                'email': email,
                'url': url,
                'company_id': company_id,
                'level': level,
                'payroll_access': payroll_access,
                'billing_access': billing_access,
                'avatar': avatar,
                'screenshots_active': screenshots_active,
                'manual_time': manual_time,
                'permanent_tasks': permanent_tasks,
                'computer_time_popup': computer_time_popup,
                'poor_time_popup': poor_time_popup,
                'blur_screenshots': blur_screenshots,
                'web_and_app_monitoring': web_and_app_monitoring,
                'webcam_shots': webcam_shots,
                'screenshots_interval': screenshots_interval,
                'user_role_value': user_role_value,
                'active': active,
                'password': password,
            },
            (result) => {
                callback(result);
            }
        );
    }

    editUser(userId,
             full_name,
             first_name,
             last_name,
             email,
             url,
             company_id,
             level,
             payroll_access,
             billing_access,
             avatar,
             screenshots_active,
             manual_time,
             permanent_tasks,
             computer_time_popup,
             poor_time_popup,
             blur_screenshots,
             web_and_app_monitoring,
             webcam_shots,
             screenshots_interval,
             user_role_value,
             active,
             password,
             callback) {
        this.api.send(
            'users/edit',
            {
                'user_id': userId,
                'full_name': full_name,
                'first_name': first_name,
                'last_name': last_name,
                'email': email,
                'url': url,
                'company_id': company_id,
                'level': level,
                'payroll_access': payroll_access,
                'billing_access': billing_access,
                'avatar': avatar,
                'screenshots_active': screenshots_active,
                'manual_time': manual_time,
                'permanent_tasks': permanent_tasks,
                'computer_time_popup': computer_time_popup,
                'poor_time_popup': poor_time_popup,
                'blur_screenshots': blur_screenshots,
                'web_and_app_monitoring': web_and_app_monitoring,
                'webcam_shots': webcam_shots,
                'screenshots_interval': screenshots_interval,
                'user_role_value': user_role_value,
                'active': active,
                'password': password,
            },
            (result) => {
                callback(result);
            }
        );
    }

    getUser(userId, callback) {
        let user: User;

        return this.api.send(
            'users/show',
            {'user_id': userId},
            (userFromApi) => {
                console.log(userFromApi);
                user = UsersService.convertFromApi(userFromApi)
                callback(user);
            });
    }

    getUsers(callback) {
        let usersArray: User[] = [];

        return this.api.send(
            'users/list',
            [],
            (result) => {
                result.data.forEach(function (userFromApi) {
                    usersArray.push(UsersService.convertFromApi(userFromApi));
                });

                callback(usersArray);
            });
    }

    removeUser(userId, callback) {
        this.api.send(
            'users/remove',
            {
                'user_id': userId,
            },
            (result) => {
                callback(result);
            }
        );
    }

    static convertFromApi(userFromApi) {
        return new User(
            userFromApi.id,
            userFromApi.full_name,
            userFromApi.first_name,
            userFromApi.last_name,
            userFromApi.email,
            userFromApi.url,
            userFromApi.company_id,
            userFromApi.level,
            userFromApi.payroll_access,
            userFromApi.billing_access,
            userFromApi.avatar,
            userFromApi.screenshots_active,
            userFromApi.manual_time,
            userFromApi.permanent_tasks,
            userFromApi.computer_time_popup,
            userFromApi.poor_time_popup,
            userFromApi.blur_screenshots,
            userFromApi.web_and_app_monitoring,
            userFromApi.webcam_shots,
            userFromApi.screenshots_interval,
            userFromApi.user_role_value,
            userFromApi.active,
            userFromApi.password,
            userFromApi.created_at,
            userFromApi.updated_at,
            userFromApi.deleted_at
        )
    }
}
