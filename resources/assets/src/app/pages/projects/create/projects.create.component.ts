import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from "../../../models/project.model";
import {Router} from "@angular/router";


@Component({
    selector: 'app-projects-create',
    templateUrl: './projects.create.component.html',
    styleUrls: ['./projects.create.component.scss']
})

export class ProjectsCreateComponent implements OnInit {
    constructor(
        private api: ApiService,
        private router: Router
    ) {}

    public model: Project = new Project();


    ngOnInit() {}

    public onSubmit() {
        this.api.send(
            'projects/create',
            {
                'company_id': this.model.company_id,
                'name': this.model.name,
                'description': this.model.description
            },
            (result) => {
                console.log(result);
                this.router.navigateByUrl('/projects/list');
            }
        );
    }


}
