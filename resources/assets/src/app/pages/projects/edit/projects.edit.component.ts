import {Component, IterableDiffers, OnInit, IterableDiffer} from '@angular/core';
import {Router, ActivatedRoute} from '@angular/router';
import {DualListComponent} from 'angular-dual-listbox';
import {TranslateService} from '@ngx-translate/core';

import {ApiService} from '../../../api/api.service';
import {ProjectsService} from '../projects.service';
import { RolesService } from '../../roles/roles.service';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {UsersService} from '../../users/users.service';

import {Project} from '../../../models/project.model';
import { Role } from '../../../models/role.model';
import { User } from '../../../models/user.model';

import {ItemsEditComponent} from '../../items.edit.component';

type ProjectWithRoles = Project & { roles?: Role[] };

@Component({
    selector: 'app-projects-edit',
    templateUrl: './projects.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ProjectsEditComponent extends ItemsEditComponent implements OnInit {
    public item: ProjectWithRoles = new Project();

    format: any = DualListComponent.DEFAULT_FORMAT;

    users: User[] = [];
    attachedUsers: User[] = [];
    differUsers: IterableDiffer<User>;

    roles: Role[] = [];
    attachedRoles: Role[] = [];
    differRoles: IterableDiffer<Role>;

    constructor(api: ApiService,
                protected projectService: ProjectsService,
                activatedRoute: ActivatedRoute,
                router: Router,
                allowedService: AllowedActionsService,
                translate: TranslateService,
                protected usersService: UsersService,
                protected rolesService: RolesService,
                differs: IterableDiffers) {
        super(api, projectService, activatedRoute, router, allowedService);
        this.differUsers = differs.find([]).create(null);
        this.differRoles = differs.find([]).create(null);

        translate.get('control.add').subscribe((res: string) => { this.format.add = res});
        translate.get('control.remove').subscribe((res: string) => { this.format.remove = res});
        translate.get('control.all').subscribe((res: string) => { this.format.all = res});
        translate.get('control.none').subscribe((res: string) => { this.format.none = res});
    }

    ngOnInit() {
        this.sub = this.activatedRoute.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), {'with': 'users,roles'});
        this.usersService.getItems(this.setUsers.bind(this));
        this.rolesService.getItems(this.setRoles.bind(this));
    }

    setItem(result) {
        this.item = result;
        this.attachedUsers = this.item.users;
        this.attachedRoles = this.item.roles;
        this.differUsers.diff(this.attachedUsers);
        this.differRoles.diff(this.attachedRoles);
    }

    onSubmit() {
        super.onSubmit();
        const addUsers = [];
        const removeUsers = [];
        const userChanges = this.differUsers.diff(this.attachedUsers);

        if (userChanges) {
            userChanges.forEachAddedItem((record) => {
                addUsers.push({
                    'user_id': record.item.id,
                    'project_id': this.id
                });
            });

            userChanges.forEachRemovedItem((record) => {
                removeUsers.push({
                    'user_id': record.item.id,
                    'project_id': this.id
                });
            });
        }

        if (addUsers.length > 0) {
            this.projectService.createUsers(addUsers, this.editBulkCallback.bind(this, 'Users'));
        }

        if (removeUsers.length > 0) {
            this.projectService.removeUsers(removeUsers, this.editBulkCallback.bind(this, 'Users'));
        }

        const addRoles = [];
        const removeRoles = [];
        const roleChanges = this.differRoles.diff(this.attachedRoles);

        if (roleChanges) {
            roleChanges.forEachAddedItem((record) => {
                addRoles.push({
                    'role_id': record.item.id,
                    'project_id': this.id,
                });
            });

            roleChanges.forEachRemovedItem((record) => {
                removeRoles.push({
                    'role_id': record.item.id,
                    'project_id': this.id,
                });
            });
        }

        if (addRoles.length > 0) {
            this.projectService.createRoles(addRoles, this.editBulkCallback.bind(this, 'Roles'));
        }

        if (removeRoles.length > 0) {
            this.projectService.removeRoles(removeRoles, this.editBulkCallback.bind(this, 'Roles'));
        }
    }

    setUsers(result) {
        this.users = result;
    }

    setRoles(result) {
        this.roles = result;
    }

    prepareData() {
        return {
            // 'company_id': this.item.company_id,
            'name': this.item.name,
            'description': this.item.description,
        };
    }
    getHeader() {
        return 'Edit Project';
    }

    getFields() {
        return [
            {'label': 'Company Id', 'name': 'project-company-id', 'model': 'company_id'},
            {'label': 'Name', 'name': 'project-name', 'model': 'name'},
            {'label': 'Description', 'name': 'project-description', 'model': 'description'},
        ];
    }
}
