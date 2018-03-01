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
        this.projectService.createProject(
            this.project.company_id,
            this.project.name,
            this.project.description,
            this.createCallback.bind(this)
        );
    }

    createCallback(result) {
        console.log(result);
        this.router.navigateByUrl('/projects/list');
    }


}
