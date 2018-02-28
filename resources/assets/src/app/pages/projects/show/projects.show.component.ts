import { Component, OnInit } from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from "../../../models/project.model";
import {ProjectsService} from "../projects.service";
import {ActivatedRoute} from "@angular/router";

@Component({
    selector: 'app-projects-show',
    templateUrl: './projects.show.component.html',
    styleUrls: ['./projects.show.component.scss']
})
export class ProjectsShowComponent implements OnInit {
    id: number;
    private sub: any;
    public project: Project = new Project();

    constructor(
        private api: ApiService,
        private projectService: ProjectsService,
        private router: ActivatedRoute
    ) {}

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.projectService.getProject(this.id, this.setProject.bind(this));
    }

    setProject(result) {
        this.project = result;
    }
}
