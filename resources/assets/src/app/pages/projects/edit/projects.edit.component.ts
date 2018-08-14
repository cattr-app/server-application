import {Component, IterableDiffers, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ProjectsService} from '../projects.service';
import {Router, ActivatedRoute} from '@angular/router';
import {Project} from '../../../models/project.model';
import {ItemsEditComponent} from '../../items.edit.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {DualListComponent} from 'angular-dual-listbox';
import {UsersService} from '../../users/users.service';
import {TranslateService} from '@ngx-translate/core';

@Component({
    selector: 'app-projects-edit',
    templateUrl: './projects.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ProjectsEditComponent extends ItemsEditComponent implements OnInit {

    public item: Project = new Project();
    sourceUsers: any = [];
    confirmedUsers: any = [];
    key = 'id';
    displayUsers: any = 'full_name';
    keepSorted = true;
    filter = true;
    height = '250px';
    format: any = DualListComponent.DEFAULT_FORMAT;
    differUsers: any;

    constructor(api: ApiService,
                protected projectService: ProjectsService,
                activatedRoute: ActivatedRoute,
                router: Router,
                allowedService: AllowedActionsService,
                translate: TranslateService,
                protected usersService: UsersService,
                differs: IterableDiffers) {
        super(api, projectService, activatedRoute, router, allowedService);
        this.differUsers = differs.find([]).create(null);

        translate.get('control.add').subscribe((res: string) => { this.format.add = res});
        translate.get('control.remove').subscribe((res: string) => { this.format.remove = res});
        translate.get('control.all').subscribe((res: string) => { this.format.all = res});
        translate.get('control.none').subscribe((res: string) => { this.format.none = res});
    }

    ngOnInit() {
        this.sub = this.activatedRoute.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this), {'with': 'users'});
        this.usersService.getItems(this.UsersUpdate.bind(this));
    }

    setItem(result) {
        this.item = result;
        this.confirmedUsers = this.item.users;
        this.differUsers.diff(this.confirmedUsers);
    }

    onSubmit() {
        super.onSubmit();
        const addUsers = [];
        const removeUsers = [];
        const UserChanges = this.differUsers.diff(this.confirmedUsers);

        if (UserChanges) {
            UserChanges.forEachAddedItem((record) => {
                addUsers.push(
                    {
                        'user_id': record.item.id,
                        'project_id': this.id
                    },
                );
            });
            UserChanges.forEachRemovedItem((record) => {
                removeUsers.push(
                    {
                        'user_id': record.item.id,
                        'project_id': this.id
                    },
                );
            });
        }

        if (addUsers.length > 0) {
            this.projectService.createUsers(addUsers, this.editBulkCallback.bind(this, 'Users'));
        }

        if (removeUsers.length > 0) {
            this.projectService.removeUsers(removeUsers, this.editBulkCallback.bind(this, 'Users'));
        }
    }

    UsersUpdate(result) {
        this.sourceUsers = result;
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
