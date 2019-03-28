import { Component, OnInit, IterableDiffers, IterableDiffer, ViewChild, ChangeDetectorRef } from '@angular/core';
import { ApiService } from '../../../api/api.service';
import { User } from '../../../models/user.model';
import { Router, ActivatedRoute } from '@angular/router';
import { UsersService } from '../users.service';
import { ItemsEditComponent } from '../../items.edit.component';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { Role } from '../../../models/role.model';
import { RedmineStatus } from '../../../models/redmine-status.model';
import { RedmineService } from '../redmine.service';
import { RolesService } from '../../roles/roles.service';
import { ProjectsService } from '../../projects/projects.service';
import { Project } from '../../../models/project.model';
import { DualListComponent } from 'angular-dual-listbox';
import { TimezonePickerComponent } from 'ng2-timezone-selector';
import {LocalStorage} from '../../../api/storage.model';

type UserWithProjects = User & { projects?: Project[] };

@Component({
    selector: 'app-users-edit',
    templateUrl: './users.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersEditComponent extends ItemsEditComponent implements OnInit {
    @ViewChild('timezone') timezone: TimezonePickerComponent;

    public item: UserWithProjects = new User();
    public roles: Role[] = [];

    projects: Project[];
    userProjects: Project[];
    differProjects: IterableDiffer<Project>;
    dualListFormat: any = DualListComponent.DEFAULT_FORMAT;
    authorizedUser: User;
    redmineStatuses: RedmineStatus[] = [];
    redmineIgnoreStatuses: boolean[] = [];


    constructor(api: ApiService,
        userService: UsersService,
        activatedRoute: ActivatedRoute,
        router: Router,
        allowedService: AllowedActionsService,
        protected roleService: RolesService,
        protected projectService: ProjectsService,
        protected redmineService: RedmineService,
        differs: IterableDiffers,
        protected cdr: ChangeDetectorRef,
    ) {
        super(api, userService, activatedRoute, router, allowedService);
        this.differProjects = differs.find([]).create(null);
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

    ngOnInit() {
        this.sub = this.activatedRoute.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), { 'with': 'projects' });
        this.roleService.getItems(this.setRoles.bind(this));
        this.projectService.getItems(this.setProjects.bind(this));
        this.redmineService.getStatuses(this.setRedmineStatuses.bind(this));
        this.authorizedUser = LocalStorage.getStorage().get("user");
        // Needed to avoid 'Expression has changed after it was checked' error in the timezone picker.
        this.cdr.detectChanges();
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

    setRedmineStatuses(redmineStatuses) {
        this.redmineStatuses = redmineStatuses;
    }

    setProjects(result) {
        this.projects = result;
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
