export class User {
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
    public active: string;
    public password: string;
    public created_at?: string;
    public updated_at?: string;
    public deleted_at?: string;

    constructor(id?,
                full_name?,
                first_name?,
                last_name?,
                email?,
                url?,
                company_id?,
                level?,
                payroll_access?,
                billing_access?,
                avatar?,
                screenshots_active?,
                manual_time?,
                permanent_tasks?,
                computer_time_popup?,
                poor_time_popup?,
                blur_screenshots?,
                web_and_app_monitoring?,
                webcam_shots?,
                screenshots_interval?,
                user_role_value?,
                active?,
                password?,
                createdAt?,
                updatedAt?,
                deletedAt?) {
        this.id = id;
        this.full_name = full_name;
        this.first_name = first_name;
        this.last_name = last_name;
        this.email = email;
        this.url = url;
        this.company_id = company_id;
        this.level = level;
        this.payroll_access = payroll_access;
        this.billing_access = billing_access;
        this.avatar = avatar;
        this.screenshots_active = screenshots_active;
        this.manual_time = manual_time;
        this.permanent_tasks = permanent_tasks;
        this.computer_time_popup = computer_time_popup;
        this.poor_time_popup = poor_time_popup;
        this.blur_screenshots = blur_screenshots;
        this.web_and_app_monitoring = web_and_app_monitoring;
        this.webcam_shots = webcam_shots;
        this.screenshots_interval = screenshots_interval;
        this.user_role_value = user_role_value;
        this.active = active;
        this.password = password;
        this.created_at = createdAt;
        this.updated_at = updatedAt;
        this.deleted_at = deletedAt;

    }
}
