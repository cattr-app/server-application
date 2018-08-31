import {Component, DoCheck, IterableDiffers, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TasksService} from '../tasks.service';
import {Task} from '../../../models/task.model';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ProjectsService} from '../../projects/projects.service';
import {LocalStorage} from '../../../api/storage.model';

@Component({
    selector: 'app-tasks-list',
    templateUrl: './tasks.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TasksListComponent extends ItemsListComponent implements OnInit, DoCheck {

    itemsArray: Task[] = [];
    p = 1;
    userId: number = null;
    projectId: number = null;
    differUser: any;
    differProject: any;
    directProject: any = [];
    filterByUser: any[];
    filterByProject: any[];

    constructor(api: ApiService,
                taskService: TasksService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,
                differs: IterableDiffers,
                protected projectService: ProjectsService
    ) {
        super(api, taskService, modalService, allowedService);
        this.differUser = differs.find([]).create(null);
        this.differProject = differs.find([]).create(null);
    }

    ngOnInit() {
        this.filterByUser = LocalStorage.getStorage().get(`filterByUserIN${ window.location.pathname }`);
        this.filterByUser instanceof Array ? this.filterByUser : new Array();
        
        this.filterByProject = LocalStorage.getStorage().get(`filterByProjectIN${ window.location.pathname }`);
        this.filterByProject instanceof Array ? this.filterByProject : new Array();
    }

    setProjects(result) {
        const items = [];
        result.forEach(function (item) {
            items.push(item.id);
            return item;
        });
        this.directProject = items;
    }

    ngDoCheck() {
        const changeUserId = this.differUser.diff([this.userId]);
        const changeProjectId = this.differProject.diff([this.projectId]);
        const filter = {'with': 'project'};

        if (changeUserId || changeProjectId) {
            if (this.userId) {
                filter['user_id'] = ['=', this.userId];
            } else if (this.filterByUser.length > 0) {
                filter['user_id'] = ['=', this.filterByUser];
            }

            if (this.projectId) {
                filter['project_id'] = ['=', this.projectId];
            } else if(this.filterByProject.length > 0) {
                filter['project_id'] = ['=', this.filterByProject];
            }
            this.itemService.getItems(this.setItems.bind(this), filter ? filter : {'active': 1});
            this.projectService.getItems(this.setProjects.bind(this), this.can('/projects/full_access') ? [] : {'direct_relation': 1});
        }
    }
}
