import {Item} from './item.model';

export interface UserData {
   id: number;
   full_name: string;
   first_name: string;
   last_name: string;
   email: string;
   url: string;
   company_id: number;
   level: string;
   payroll_access: number;
   billing_access: number;
   avatar: string;
   screenshots_active: number;
   manual_time: number;
   permanent_tasks: number;
   computer_time_popup: number;
   poor_time_popup: string;
   blur_screenshots: number;
   web_and_app_monitoring: number;
   webcam_shots: number;
   screenshots_interval: number;
   user_role_value: string;
   active: number;
   password: string;
   timezone: string;
   role_id: number;
   created_at?: string;
   updated_at?: string;
   deleted_at?: string;
   redmine_sync?: number;
   redmine_active_status?: number;
   redmine_deactive_status?: number;
   redmine_ignore_statuses?: number[];
   attached_users?: User[];
}

export class User extends Item {
    public id: number;
    public full_name: string;
    public first_name: string;
    public last_name: string;
    public email: string;
    public url: string;
    public company_id: number;
    public level: string;
    public payroll_access: number;
    public billing_access: number;
    public avatar: string;
    public screenshots_active: number;
    public manual_time: number;
    public permanent_tasks: number;
    public computer_time_popup: number;
    public poor_time_popup: string;
    public blur_screenshots: number;
    public web_and_app_monitoring: number;
    public webcam_shots: number;
    public screenshots_interval: number;
    public user_role_value: string;
    public active: number;
    public password: string;
    public timezone: string;
    public role_id: number;
    public redmine_active_status: number;
    public redmine_deactive_status: number;
    public redmine_ignore_statuses: number[] = [];
    public created_at?: string;
    public updated_at?: string;
    public deleted_at?: string;
    public redmine_sync?: number;
    public attached_users?: User[];

    constructor(data?: UserData) {
        super();

        if (data) {
            for (const key in data) {
                this[key] = data[key];
            }
        }
    }
}
