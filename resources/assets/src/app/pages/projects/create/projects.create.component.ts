import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from "../../../models/project.model";
import {Router} from "@angular/router";
import {ProjectsService} from "../projects.service";
import {ItemsCreateComponent} from "../../items.create.component";


@Component({
    selector: 'app-projects-create',
    templateUrl: './projects.create.component.html',
    styleUrls: ['./projects.create.component.scss']
})

export class ProjectsCreateComponent extends ItemsCreateComponent implements OnInit {

    public item: Project = new Project();

    constructor(api: ApiService,
                projectService: ProjectsService,
                router: Router) {
        super(api, projectService, router);
    }

    prepareData() {
        return {
            'company_id': this.item.company_id,
            'name': this.item.name,
            'description': this.item.description,
        }
    }
}
