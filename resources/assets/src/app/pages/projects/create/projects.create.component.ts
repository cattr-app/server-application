import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from "../../../models/project.model";
import {Router} from "@angular/router";
import {ProjectsService} from "../projects.service";


@Component({
    selector: 'app-projects-create',
    templateUrl: './projects.create.component.html',
    styleUrls: ['./projects.create.component.scss']
})

export class ProjectsCreateComponent implements OnInit {

    public project: Project = new Project();

    constructor(private api: ApiService,
                private projectService: ProjectsService,
                private router: Router) {
    }

    ngOnInit() {
    }

    public onSubmit() {
        this.projectService.createItem(
            this.prepareData(),
            this.createCallback.bind(this)
        );
    }

    prepareData() {
        return {
            'company_id':     this.project.company_id,
            'name': this.project.name,
            'description': this.project.description,
        }
    }

    createCallback(result) {
        this.router.navigateByUrl('/projects/list');
    }


}
