import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ProjectsService} from "../projects.service";
import {ActivatedRoute} from '@angular/router';
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

    constructor(private api: ApiService,
                private projectService: ProjectsService,
                private router: ActivatedRoute) {
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.projectService.getItem(this.id, this.setProject.bind(this));
    }

    setProject(result) {
        this.project = result;
    }

    public onSubmit() {
        this.projectService.editItem(
            this.id,
            this.prepareData(),
            this.editCallback.bind(this)
        );
    }

    prepareData() {
        return {
            'company_id': this.project.company_id,
            'name': this.project.name,
            'description': this.project.description,
        }
    }

    editCallback(result) {
        console.log("Updated");
    }
}
