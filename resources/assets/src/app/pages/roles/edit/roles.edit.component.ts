import {Component, OnInit, OnDestroy, IterableDiffers} from '@angular/core';
import {Router, ActivatedRoute} from '@angular/router';

import {Role} from '../../../models/role.model';
import { Project } from '../../../models/project.model';
import {User} from '../../../models/user.model';

import {ItemsEditComponent} from '../../items.edit.component';
import {DualListComponent} from 'angular-dual-listbox';

import {ApiService} from '../../../api/api.service';
import {RolesService} from '../roles.service';
import {RulesService} from '../rules.service';
import {UsersService} from '../../users/users.service';
import {AllowedActionsService} from '../allowed-actions.service';
import {TranslateService} from '@ngx-translate/core';
import { ProjectsService } from '../../projects/projects.service';

type RoleWithProjects = Role & { projects?: Project[] };

@Component({
    selector: 'app-roles-edit',
    templateUrl: './roles.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesEditComponent extends ItemsEditComponent implements OnInit, OnDestroy {

    public item: RoleWithProjects = new Role();
    user: User;
    sourceRules: any = [];
    confirmedRules: any = [];
    sourceUsers: any = [];
    confirmedUsers: any = [];
    sourceProjects: Project[] = [];
    confirmedProjects: Project[] = [];
    key = 'id';
    displayRules = 'name';
    displayUsers = 'full_name';
    displayProjects = 'name';
    keepSorted = true;
    filter = true;
    height = '250px';
    format: any = DualListComponent.DEFAULT_FORMAT;
    differUsers: any;
    differRules: any;
    differProjects: any;

    constructor(api: ApiService,
                roleService: RolesService,
                activatedRoute: ActivatedRoute,
                router: Router,
                translate: TranslateService,
                protected allowedService: AllowedActionsService,
                protected ruleService: RulesService,
                protected projectsService: ProjectsService,
                protected usersService: UsersService,
                differs: IterableDiffers) {
        super(api, roleService, activatedRoute, router, allowedService);
        this.differUsers = differs.find([]).create(null);
        this.differRules = differs.find([]).create(null);
        this.differProjects = differs.find([]).create(null);

        translate.get('control.add').subscribe((res: string) => { this.format.add = res});
        translate.get('control.remove').subscribe((res: string) => { this.format.remove = res});
        translate.get('control.all').subscribe((res: string) => { this.format.all = res});
        translate.get('control.none').subscribe((res: string) => { this.format.none = res});
    }

    prepareData() {
        return {
            'name': this.item.name,
        };
    }

    ngOnInit() {
        this.sub = this.activatedRoute.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), {'with': 'projects'});
        this.usersService.getItems(this.UsersUpdate.bind(this));
        this.ruleService.getActions(this.ActionsUpdate.bind(this));
        this.projectsService.getItems(this.ProjectsUpdate.bind(this));
        this.UserUpdate();
    }

    setItem(result) {
        super.setItem(result);
        this.confirmedProjects = this.item.projects;
        this.differProjects.diff(this.confirmedProjects);
    }

    onSubmit() {
        super.onSubmit();
        const id = this.id;
        const rules = [];
        const users = [];
        const addProjects = [];
        const removeProjects = [];
        const RulesChanges = this.differRules.diff(this.confirmedRules);
        const UserChanges = this.differUsers.diff(this.confirmedUsers);
        const ProjectsChanges = this.differProjects.diff(this.confirmedProjects);

        if (UserChanges) {
            UserChanges.forEachAddedItem((record) => {
                record.item.role_id = id;
                users.push(record.item);
            });
            UserChanges.forEachRemovedItem((record) => {
                record.item.role_id = null;
                users.push(record.item);
            });
        }

        if (users.length > 0) {
            this.usersService.editItems(users, this.editBulkCallback.bind(this, 'Users'));
        }

        if (RulesChanges) {
            RulesChanges.forEachAddedItem((record) => {
                record.item.allow = 1;
                rules.push(record.item);
            });
            RulesChanges.forEachRemovedItem((record) => {
                record.item.allow = 0;
                rules.push(record.item);
            });
        }

        if (rules.length > 0) {
            this.ruleService.editItems(id, rules, this.editBulkCallback.bind(this, 'Rules'), this.errorCallback.bind(this));
        }

        if (ProjectsChanges) {
            ProjectsChanges.forEachAddedItem((record) => {
                addProjects.push({
                    'role_id': this.id,
                    'project_id': record.item.id,
                });
            });

            ProjectsChanges.forEachRemovedItem((record) => {
                removeProjects.push({
                    'role_id': this.id,
                    'project_id': record.item.id,
                });
            });
        }

        if (addProjects.length > 0) {
            this.projectsService.createRoles(addProjects, this.editBulkCallback.bind(this, 'Roles'));
        }

        if (removeProjects.length > 0) {
            this.projectsService.removeRoles(removeProjects, this.editBulkCallback.bind(this, 'Roles'));
        }
    }

    editBulkCallback(name, results) {
        const errors = [];
        for (const msg of results.messages) {
            if (msg.error) {
                let reason = '';
                if (Object.keys(msg.reason).length > 0) {
                    Object.keys(msg.reason).forEach(function (element, index) {
                        reason += ' ' + msg.reason[element][0];
                    });
                } else {
                   reason = msg.reason;
                }
                errors.push({'error': msg.error, 'reason': reason});
            }
        }

        if (errors.length > 0) {
            for (const err of errors) {
                this.msgs.push({severity: 'error', summary: name + ' ' + err.error, detail: err.reason});
            }
        } else {
            this.msgs.push({severity: 'success', summary: 'Success', detail:  name + ' has been updated'});
        }
    }

    errorCallback(result) {
        this.msgs.push({severity: 'error', summary: result.error.error, detail: result.error.reason});
    }

    UserUpdate() {
        this.user = this.api.getUser();
        if (this.id === this.user.role_id && !this.can('rules/full_access')) {
            this.router.navigateByUrl('/roles/list');
        }
    }

    ActionsUpdate(result) {
        for (const item of result) {
            item['id'] = this.sourceRules.length;
            this.sourceRules.push(item);
        }
        this.allowedService.getItems(this.AllowedUpdate.bind(this), this.id);
    }

    AllowedUpdate(result) {
        this.confirmedRules = this.sourceRules.filter(function (action) {
            return result.some((item) => (item.object === action.object && item.action === action.action));
        });
        this.differRules.diff(this.confirmedRules);
    }

    UsersUpdate(result) {
        this.sourceUsers = result;
        const id = this.id;
        this.confirmedUsers = this.sourceUsers.filter(function (user) {
                return user.role_id === id;
            });
        this.differUsers.diff(this.confirmedUsers);
    }

    ProjectsUpdate(result) {
        this.sourceProjects = result;
    }


    cleanupParams() : string[] {
        return [
            'item',
            'user',
            'sourceRules',
            'confirmedRules',
            'sourceUsers',
            'confirmedUsers',
            'sourceProjects',
            'confirmedProjects',
            'key',
            'displayRules',
            'displayUsers',
            'displayProjects',
            'keepSorted',
            'filter',
            'height',
            'format',
            'differUsers',
            'differRules',
            'differProjects',
            'api',
            'roleService',
            'activatedRoute',
            'router',
            'allowedService',
            'ruleService',
            'projectsService',
            'usersService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
