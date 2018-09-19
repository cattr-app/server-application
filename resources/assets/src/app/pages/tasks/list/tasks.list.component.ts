import {Component, DoCheck, IterableDiffers, OnInit, OnChanges, SimpleChanges} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TasksService} from '../tasks.service';
import {Task} from '../../../models/task.model';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ProjectsService} from '../../projects/projects.service';
import {LocalStorage} from '../../../api/storage.model';

enum TasksOrder {
    IdAsc,
    IdDesc,
    ProjectAsc,
    ProjectDesc,
    NameAsc,
    NameDesc,
}

@Component({
    selector: 'app-tasks-list',
    templateUrl: './tasks.list.component.html',
    styleUrls: ['./tasks.list.component.scss', '../../items.component.scss']
})
export class TasksListComponent extends ItemsListComponent implements OnInit, DoCheck {

    itemsArray: Task[] = [];
    p = 1;
    userId?: number[] = [];
    projectId?: number[] = [];
    differUser: any;
    differProject: any;
    directProject: any = [];
    order: TasksOrder = TasksOrder.IdAsc;

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
        const userId = LocalStorage.getStorage().get(`filterByUserIN${window.location.pathname}`);
        this.userId = userId instanceof Array ? userId : [];

        const projectId = LocalStorage.getStorage().get(`filterByProjectIN${window.location.pathname}`);
        this.projectId = projectId instanceof Array ? projectId : [];
    }

    setProjects(result) {
        const items = [];
        result.forEach(function (item) {
            items.push(item.id);
            return item;
        });
        this.directProject = items;
    }

    load() {
        const filter = {
            'with': 'project',
        };

        if (this.userId && this.userId.length) {
            filter['user_id'] = ['=', this.userId];
        }

        if (this.projectId && this.projectId.length) {
            filter['project_id'] = ['=', this.projectId];
        }

        this.itemService.getItems(this.setItems.bind(this), filter ? filter : { 'active': 1 });
        this.projectService.getItems(this.setProjects.bind(this), this.can('/projects/full_access')
            ? []
            : { 'direct_relation': 1 });
    }

    setItems(result: Task[]) {
        this.itemsArray = result.sort((a, b) => {
            switch(this.order) {
                default:
                case TasksOrder.IdAsc: return a.id - b.id;
                case TasksOrder.IdDesc: return b.id - a.id;
                case TasksOrder.ProjectAsc: return a.project.name.localeCompare(b.project.name);
                case TasksOrder.ProjectDesc: return b.project.name.localeCompare(a.project.name);
                case TasksOrder.NameAsc: return a.task_name.localeCompare(b.task_name);
                case TasksOrder.NameDesc: return b.task_name.localeCompare(a.task_name);
            }
        });
    }

    setOrder(order: string) {
        switch (order) {
            default:
            case 'id': this.order = this.order === TasksOrder.IdAsc ? TasksOrder.IdDesc : TasksOrder.IdAsc; break;
            case 'project': this.order = this.order === TasksOrder.ProjectAsc ? TasksOrder.ProjectDesc : TasksOrder.ProjectAsc; break;
            case 'name': this.order = this.order === TasksOrder.NameAsc ? TasksOrder.NameDesc : TasksOrder.NameAsc; break;
        }
        this.load();
    }

    ngDoCheck() {
        const changeUserId = this.differUser.diff([this.userId]);
        const changeProjectId = this.differProject.diff([this.projectId]);

        if (changeUserId || changeProjectId) {
            this.load();
        }
    }
}
