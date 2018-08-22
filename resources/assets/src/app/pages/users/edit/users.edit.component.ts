import {Component, OnInit, IterableDiffers, IterableDiffer} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {User} from '../../../models/user.model';
import {Router, ActivatedRoute} from '@angular/router';
import {UsersService} from '../users.service';
import {ItemsEditComponent} from '../../items.edit.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {Role} from '../../../models/role.model';
import {RolesService} from '../../roles/roles.service';
import { ProjectsService } from '../../projects/projects.service';
import { Project } from '../../../models/project.model';
import { DualListComponent } from 'angular-dual-listbox';

type UserWithProjects = User & { projects?: Project[] };

@Component({
    selector: 'app-users-edit',
    templateUrl: './users.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersEditComponent extends ItemsEditComponent implements OnInit {

    public item: UserWithProjects = new User();
    public roles: Role[];
    public active = [
        {value: 0, label: 'Inactive'},
        {value: 1, label: 'Active'},
    ];
    public selectedActive: any;
    public selectedRole: any;

    projects: Project[];
    userProjects: Project[];
    differProjects: IterableDiffer<Project>;
    dualListFormat: any = DualListComponent.DEFAULT_FORMAT;

    constructor(api: ApiService,
                userService: UsersService,
                activatedRoute: ActivatedRoute,
                router: Router,
                allowedService: AllowedActionsService,
                protected roleService: RolesService,
                protected projectService: ProjectsService,
                differs: IterableDiffers
    ) {
        super(api, userService, activatedRoute, router, allowedService);
        this.differProjects = differs.find([]).create(null);
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
            'timezone': this.item.timezone,
            'role_id': this.item.role_id,
        };
    }

    ngOnInit() {
        this.sub = this.activatedRoute.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), {'with': 'projects'});
        this.roleService.getItems(this.setRoles.bind(this));
        this.projectService.getItems(this.setProjects.bind(this));
    }

    setItem(result) {
        this.item = result;
        this.selectedActive = this.active.find((i) => i.value === parseInt(result.active, 2));
        this.selectedRole = result.role_id;
        this.userProjects = this.item.projects;
        this.differProjects.diff(this.userProjects);
    }

    setRoles(result) {
        this.roles = result;
    }

    setProjects(result) {
        this.projects = result;
    }

    OnChangeSelectActive(event) {
        if (event) {
            this.item.active = event.value;
        }
    }

    OnChangeSelectRole(event) {
        if (event) {
            this.item.role_id = event.id;
        }
    }

    onSubmit() {
        super.onSubmit();
        const addProjects = [];
        const removeProjects = [];
        const changes = this.differProjects.diff(this.userProjects);

        if (changes) {
            changes.forEachAddedItem(record => {
                addProjects.push({
                    'user_id': this.id,
                    'project_id': record.item.id,
                });
            });

            changes.forEachRemovedItem(record => {
                removeProjects.push({
                    'user_id': this.id,
                    'project_id': record.item.id,
                });
            });
        }

        if (addProjects.length > 0) {
            this.projectService.createUsers(addProjects, this.editBulkCallback.bind(this, 'Projects'));
        }

        if (removeProjects.length > 0) {
            this.projectService.removeUsers(removeProjects, this.editBulkCallback.bind(this, 'Projects'));
        }
    }
}
