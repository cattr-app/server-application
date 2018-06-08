import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from '../../../models/project.model';
import {ProjectsService} from '../projects.service';
import {ActivatedRoute} from '@angular/router';
import {ItemsShowComponent} from '../../items.show.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-projects-show',
    templateUrl: './projects.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ProjectsShowComponent extends ItemsShowComponent implements OnInit {

    public item: Project = new Project();

    constructor(api: ApiService,
                projectService: ProjectsService,
                router: ActivatedRoute,
                allowedService: AllowedActionsService) {
        super(api, projectService, router, allowedService);
    }
}
