import {Component, OnInit, OnDestroy} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from '../../../models/project.model';
import {Router} from '@angular/router';
import {ProjectsService} from '../projects.service';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ItemsCreateComponent} from '../../items.create.component';

@Component({
    selector: 'app-projects-create',
    templateUrl: './projects.create.component.html',
    styleUrls: ['../../items.component.scss']
})

export class ProjectsCreateComponent extends ItemsCreateComponent implements OnInit, OnDestroy {

    public item: Project = new Project();

    constructor(api: ApiService,
                projectService: ProjectsService,
                router: Router,
                allowedService: AllowedActionsService, ) {
        super(api, projectService, router, allowedService);
    }

    prepareData() {
        return {
            // 'company_id': this.item.company_id,
            'name': this.item.name,
            'description': this.item.description,
            'important': this.item.important,
        };
    }

    getHeader() {
        return 'Create New Project';
    }

    getFields() {
        return [
            {'label': 'Company Id', 'name': 'project-company-id', 'model': 'company_id'},
            {'label': 'Name', 'name': 'project-name', 'model': 'name'},
            {'label': 'Description', 'name': 'project-description', 'model': 'description'},
        ];
    }


    cleanupParams() : string[] {
        return [
            'item',
            'api',
            'itemService',
            'router',
            'allowedAction',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }

}
