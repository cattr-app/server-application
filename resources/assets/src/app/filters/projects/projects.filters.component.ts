import {Component, OnInit, Input, EventEmitter, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Router} from '@angular/router';
import { Location } from '@angular/common';
import {AttachedProjectService} from '../../pages/projects/attached-project.service';

@Component({
    selector: 'app-projects-filters',
    templateUrl: './projects.filters.component.html',
})
export class ProjectsFiltersComponent implements OnInit {
    @Input() projectId: any = null;
    @Output() projectIdChange = new EventEmitter();
    projects: Array<any> = [];
    isAuthorized = false;
    selectProjects: any = [];

    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
        protected attachedProjectsService: AttachedProjectService,
    ) {
        this.isAuthorized = apiService.isAuthorized();
    }

    ngOnInit(): void {
        this.attachedProjectsService.subscribeOnUpdate(this.onProjectUpdate.bind(this));
        this.attachedProjectsService.updateAttachedList();
    }

    onProjectUpdate(projects) {
        this.updateItems(projects);
    }

    onChange($event) {
        if ($event.length > 0) {
            this.projectId = $event.map(function(user) {
               return user.id;
            });
        } else {
            this.projectId = null;
        }
        this.projectIdChange.emit(this.projectId);
    }

    updateItems(items): void {
        if (!this.isAuthorized) {
            this.projects = [];
            return;
        }
        this.projects = items;
    }

}
