import {Component, DoCheck, IterableDiffers, OnInit, ViewChild} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TasksService} from '../tasks.service';
import {Task} from '../../../models/task.model';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ProjectsService} from '../../projects/projects.service';
import {LocalStorage} from '../../../api/storage.model';
import { Subscription } from 'rxjs';

enum TasksOrder {
    IdAsc,
    IdDesc,
    ProjectAsc,
    ProjectDesc,
    NameAsc,
    NameDesc,
    PriorityAsc,
    PriorityDesc,
}

@Component({
    selector: 'app-tasks-list',
    templateUrl: './tasks.list.component.html',
    styleUrls: ['./tasks.list.component.scss', '../../items.component.scss']
})
export class TasksListComponent extends ItemsListComponent implements OnInit, DoCheck {
    @ViewChild('loading') loading: any;

    itemsArray: Task[] = [];
    userId?: number[] = [];
    projectId?: number[] = [];
    differUser: any;
    differProject: any;
    directProject: any = [];
    order: TasksOrder = TasksOrder.IdAsc;
    scrollHandler: any = null;
    isLoading = false;
    isAllLoaded = false;
    offset = 0;
    chunksize = 25;
    requestTasks: Subscription = new Subscription();

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

    setProjects(result) {
        const items = [];
        result.forEach(function (item) {
            items.push(item.id);
            return item;
        });
        this.directProject = items;
    }

    ngOnInit() {
        const userId = LocalStorage.getStorage().get(`filterByUserIN${window.location.pathname}`);
        this.userId = userId instanceof Array ? userId : [];

        const projectId = LocalStorage.getStorage().get(`filterByProjectIN${window.location.pathname}`);
        this.projectId = projectId instanceof Array ? projectId : [];

        this.projectService.getItems(this.setProjects.bind(this), this.can('/projects/full_access')
            ? []
            : { 'direct_relation': 1 });

        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngDoCheck() {
        const changeUserId = this.differUser.diff([this.userId]);
        const changeProjectId = this.differProject.diff([this.projectId]);

        if (changeUserId || changeProjectId) {
            this.reload();
        }
    }

    setOrder(order: string) {
        switch (order) {
            default:
            case 'id':
                this.order = this.order === TasksOrder.IdAsc
                    ? TasksOrder.IdDesc : TasksOrder.IdAsc;
                break;

            case 'project':
                this.order = this.order === TasksOrder.ProjectAsc
                    ? TasksOrder.ProjectDesc : TasksOrder.ProjectAsc;
                break;

            case 'name':
                this.order = this.order === TasksOrder.NameAsc
                    ? TasksOrder.NameDesc : TasksOrder.NameAsc;
                break;

            case 'priority':
                this.order = this.order === TasksOrder.PriorityDesc
                    ? TasksOrder.PriorityAsc : TasksOrder.PriorityDesc;
                break;
        }

        this.reload();
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    loadNext() {
        if (this.isLoading || this.isAllLoaded) {
            return;
        }

        this.isLoading = true;

        const filter = {
            'with': 'project',
            'limit': this.chunksize,
            'offset': this.offset,
            'active': 1,
        };

        switch (this.order) {
            default:
            case TasksOrder.IdAsc: filter['order_by'] = 'id'; break;
            case TasksOrder.IdDesc: filter['order_by'] = ['id', 'desc']; break;
            case TasksOrder.ProjectAsc: filter['order_by'] = 'projects.name'; break;
            case TasksOrder.ProjectDesc: filter['order_by'] = ['projects.name', 'desc']; break;
            case TasksOrder.NameAsc: filter['order_by'] = 'task_name'; break;
            case TasksOrder.NameDesc: filter['order_by'] = ['task_name', 'desc']; break;
            case TasksOrder.PriorityAsc: filter['order_by'] = 'priority_id'; break;
            case TasksOrder.PriorityDesc: filter['order_by'] = ['priority_id', 'desc']; break;
        }

        if (this.userId && this.userId.length) {
            filter['user_id'] = ['=', this.userId];
        }

        if (this.projectId && this.projectId.length) {
            filter['project_id'] = ['=', this.projectId];
        }

        if (this.requestTasks.closed !== undefined && !this.requestTasks.closed) {
            this.requestTasks.unsubscribe();
        }

        this.requestTasks = this.itemService.getItems(result => {
            this.setItems(this.itemsArray.concat(result));
            this.offset += this.chunksize;
            this.isLoading = false;
            this.isAllLoaded = result.length < this.chunksize;
        }, filter);
    }

    reload() {
        this.offset = 0;
        this.isLoading = false;
        this.isAllLoaded = false;
        this.setItems([]);
        this.loadNext();
    }

    onScrollDown() {
        const block_Y_position = this.loading.nativeElement.offsetTop;
        const scroll_Y_top_position = window.scrollY;
        const windowHeight = window.innerHeight;
        const bottom_scroll_Y_position = scroll_Y_top_position + windowHeight;

        if (bottom_scroll_Y_position < block_Y_position) { // loading new tasks doesn't needs
            return;
        }

        this.loadNext();
    }
}
