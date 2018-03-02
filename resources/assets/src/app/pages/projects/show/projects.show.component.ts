import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from "../../../models/project.model";
import {ProjectsService} from "../projects.service";
import {ActivatedRoute} from "@angular/router";
import {ItemsShowComponent} from "../../items.show.component";
import {Task} from "../../../models/task.model";

@Component({
    selector: 'app-projects-show',
    templateUrl: './projects.show.component.html',
    styleUrls: ['./projects.show.component.scss']
})
export class ProjectsShowComponent extends ItemsShowComponent implements OnInit {

    public item: Project = new Project();

    constructor(api: ApiService,
                projectService: ProjectsService,
                router: ActivatedRoute) {
        super(api, projectService, router);
    }
}
