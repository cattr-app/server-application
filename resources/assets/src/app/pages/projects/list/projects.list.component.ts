import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from "../../../models/project.model";
import {ProjectsService} from "../projects.service";
import {BsModalService} from 'ngx-bootstrap/modal';
import {BsModalRef} from 'ngx-bootstrap/modal/bs-modal-ref.service';
import {Router} from "@angular/router";


@Component({
    selector: 'app-projects-list',
    templateUrl: './projects.list.component.html',
    styleUrls: ['./projects.list.component.scss']
})
export class ProjectsListComponent implements OnInit {

    projectsArray: Project[] = [];
    modalRef: BsModalRef;

    projectIdForRemoving = 0;

    constructor(private api: ApiService,
                private projectService: ProjectsService,
                private modalService: BsModalService,
                private router: Router) {

    }

    ngOnInit() {
        this.projectService.getProjects(this.setProjects.bind(this));
    }

    setProjects(result) {
        this.projectsArray = result;
    }

    removeProject() {
        this.projectService.removeProject(this.projectIdForRemoving, this.removeProjectCallback.bind(this));
        this.modalRef.hide();
    }

    ngOnDestroy() {
        this.projectsArray = [];
    }

    openRemoveProjectModalWindow(template: TemplateRef<any>, projectId) {
        this.projectIdForRemoving = projectId;
        this.modalRef = this.modalService.show(template);
    }

    removeProjectCallback(result) {
        location.reload();
    }
}
