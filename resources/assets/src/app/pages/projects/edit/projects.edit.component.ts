import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ProjectsService} from "../projects.service";
import {ActivatedRoute} from '@angular/router';
import {Project} from "../../../models/project.model";
import {ItemsEditComponent} from "../../items.edit.component";

@Component({
    selector: 'app-projects-edit',
    templateUrl: '../../items.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ProjectsEditComponent extends ItemsEditComponent implements OnInit {

    public item: Project = new Project();

    constructor(api: ApiService,
                projectService: ProjectsService,
                router: ActivatedRoute) {
        super(api, projectService, router);
    }

    prepareData() {
        return {
            'company_id': this.item.company_id,
            'name': this.item.name,
            'description': this.item.description,
        }
    }
    getHeader() {
        return "Edit Project";
    }

    getFields() {
        return [
            {'label': 'Company Id', 'name': 'project-company-id', 'model': 'company_id'},
            {'label': 'Name', 'name': 'project-name', 'model': 'name'},
            {'label': 'Description', 'name': 'project-description', 'model': 'description'},
        ];
    }

}
