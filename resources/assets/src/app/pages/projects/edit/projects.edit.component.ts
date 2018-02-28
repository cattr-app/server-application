import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ProjectsService} from "../projects.service";
import { ActivatedRoute } from '@angular/router';
import {Project} from "../../../models/project.model";


@Component({
    selector: 'app-projects-edit',
    templateUrl: './projects.edit.component.html',
    styleUrls: ['./projects.edit.component.scss']
})
export class ProjectsEditComponent implements OnInit {

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

    public onSubmit() {
        this.projectService.editProject(
            this.id,
            this.project.company_id,
            this.project.name,
            this.project.description,
            this.editCallback.bind(this)
        );
    }

    editCallback(result) {
        console.log("Updated");
    }
}
