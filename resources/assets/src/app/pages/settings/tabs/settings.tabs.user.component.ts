import { Component, ViewChild, IterableDiffer, OnInit, ChangeDetectorRef, IterableDiffers } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { DualListComponent } from 'angular-dual-listbox';
import { TimezonePickerComponent } from 'ng2-timezone-selector';
import { ItemsEditComponent } from '../../items.edit.component';

import { User } from '../../../models/user.model';
import { Role } from '../../../models/role.model';
import { Project } from '../../../models/project.model';

import { ApiService } from '../../../api/api.service';
import { UsersService } from '../../users/users.service';
import { RolesService } from '../../roles/roles.service';
import { ProjectsService } from '../../projects/projects.service';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { RedmineStatus } from '../../../models/redmine-status.model';
import { RedmineService } from '../../users/redmine.service';


type UserWithProjects = User & { projects?: Project[] };



@Component({
    selector: 'settings-user',
    templateUrl: './settings.tabs.user.component.html'
})
export class UserSettingsComponent extends ItemsEditComponent implements OnInit {
    @ViewChild('timezone') timezone: TimezonePickerComponent;

    public item: UserWithProjects = new User();
    projects: Project[];
    userProjects: Project[];
    roles: Role[] = [];
    differProjects: IterableDiffer<Project>;
    dualListFormat: any = DualListComponent.DEFAULT_FORMAT;
    redmineStatuses: RedmineStatus[] = [];
    redmineIgnoreStatuses: boolean[] = [];


    constructor(
        protected api: ApiService,
        protected allowedAction: AllowedActionsService,
        protected projectService: ProjectsService,
        protected roleService: RolesService,
        protected cdr: ChangeDetectorRef,
        protected redmineService: RedmineService,
        userService: UsersService,
        activatedRoute: ActivatedRoute,
        router: Router,
        differs: IterableDiffers,
    ) {

        super(api, userService, activatedRoute, router, allowedAction);
        this.differProjects = differs.find([]).create(null);
    }


     ngOnInit() {
        this.item.manual_time = 0;
        this.item.active = 0;
        this.item.screenshots_active = 0;
        this.item.screenshots_interval = 5;
        this.item.computer_time_popup = 5;
        this.item.timezone = '';

        this.id = this.api.getUser().id;
        this.itemService.getItem(this.id, this.setItem.bind(this), { 'with': 'projects' });
        this.roleService.getItems(this.setRoles.bind(this));
        this.projectService.getItems(this.setProjects.bind(this));
        this.redmineService.getStatuses(this.setRedmineStatuses.bind(this));
        this.cdr.detectChanges();
    }

    setRedmineStatuses(redmineStatuses) {
        this.redmineStatuses = redmineStatuses;
    }

    setItem(result) {
        this.item = result;

        this.redmineIgnoreStatuses = [];

        for (let status of this.item.redmine_ignore_statuses) {
            this.redmineIgnoreStatuses[status] = true;
        }

        this.userProjects = this.item.projects;
        this.differProjects.diff(this.userProjects);
    }

    setRoles(result) {
        this.roles = result;
    }

    setProjects(result) {
        this.projects = result;
    }


    prepareData() {

        let statuses: number[] = [];


        for (let status_id in this.redmineIgnoreStatuses) {
            if (this.redmineIgnoreStatuses[status_id]) {
                statuses.push(Number(status_id));
            }
        }

        this.item.redmine_ignore_statuses = statuses;


        return {
            'full_name': this.item.full_name,
            'first_name': this.item.first_name,
            'last_name': this.item.last_name,
            'email': this.item.email,
            'active': this.item.active,
            'role_id': this.item.role_id,
            'screenshots_active': this.item.screenshots_active,
            'manual_time': this.item.manual_time,
            'screenshots_interval': this.item.screenshots_interval,
            "computer_time_popup": this.item.computer_time_popup,
            'timezone': this.item.timezone,
            'password': this.item.password,
            'redmine_sync': this.item.redmine_sync,
            'redmine_active_status': this.item.redmine_active_status,
            'redmine_deactive_status': this.item.redmine_deactive_status,
            'redmine_online_timeout': this.item.redmine_online_timeout,
            'redmine_ignore_statuses': statuses,
        };
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
