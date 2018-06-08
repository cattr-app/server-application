import {Component, DoCheck, IterableDiffers, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from '../../../models/project.model';
import {ProjectsService} from '../projects.service';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-projects-list',
    templateUrl: './projects.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ProjectsListComponent extends ItemsListComponent implements OnInit, DoCheck {

    itemsArray: Project[] = [];
    p = 1;
    userId: any = '';
    differ: any;

    constructor(api: ApiService,
                projectService: ProjectsService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,
                differs: IterableDiffers,
                ) {
        super(api, projectService, modalService, allowedService);
        this.differ = differs.find([]).create(null);
    }

    ngOnInit() {
    }

    ngDoCheck() {
        const changeId = this.differ.diff([this.userId]);
        if (changeId) {
            this.itemService.getItems(this.setItems.bind(this), this.userId ? {'user_id': ['=', this.userId]} : null);
        }
    }
}
