import {Component, OnInit, OnDestroy} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Task} from '../../../models/task.model';
import {Router} from '@angular/router';
import {TasksService} from '../tasks.service';
import {ItemsCreateComponent} from '../../items.create.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {User} from '../../../models/user.model';
import {Project} from '../../../models/project.model';
import {ProjectsService} from '../../projects/projects.service';
import {UsersService} from '../../users/users.service';

@Component({
    selector: 'app-tasks-create',
    templateUrl: './tasks.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class TasksCreateComponent extends ItemsCreateComponent implements OnInit, OnDestroy {

    public item: Task = new Task();
    public projects: Project[];
    public users: User[];
    public selectedProject: any = null;
    public selectedUser: any = null;

    internalPriorities = [
        { id: 0, name: 'Not set', },
        { id: 1, name: 'Low', },
        { id: 2, name: 'Normal', },
        { id: 3, name: 'High', },
    ];

    constructor(api: ApiService,
                taskService: TasksService,
                router: Router,
                allowedService: AllowedActionsService,
                protected projectService: ProjectsService,
                protected userService: UsersService) {
        super(api, taskService, router, allowedService);
        this.item.active = 1;
        this.item.priority_id = 2;
        this.item.assigned_by = this.api.getUser().id;
    }

    ngOnInit() {
        super.ngOnInit();
        this.projectService.getItems(this.setProjects.bind(this), this.can('/projects/full_access') ? {} : {'direct_relation': 1});
        this.userService.getItems(this.setUsers.bind(this));
    }

    cleanupParams() : string[] {
        return [
            'item',
            'projects',
            'users',
            'selectedProject',
            'selectedUser',
            'internalPriorities',
            'api',
            'taskService',
            'router',
            'allowedService',
            'projectService',
            'userService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }

    OnChangeSelectProject(result) {
        if (result) {
            this.item.project_id = result.id;
        } else {
            this.item.project_id = null;
        }
    }

    setProjects(result) {
        this.projects = result;
    }

    OnChangeSelectUser(result) {
        if (result) {
            this.item.user_id = result.id;
        } else {
            this.item.user_id = null;
        }
    }

    setUsers(result) {
        this.users = result;
        if (this.users.length < 2) {
            const currentUser = this.api.getUser();
            this.item.user_id = currentUser.id;
            this.item.assigned_by = currentUser.id;
        }
        console.log(this.item);
    }

    prepareData() {
        return {
            'project_id': this.item.project_id,
            'task_name': this.item.task_name,
            'description': this.item.description,
            'active': this.item.active,
            'user_id': this.item.user_id,
            'assigned_by': this.item.assigned_by,
            'url': this.item.url,
            'priority_id': this.item.priority_id,
            'important': this.item.important,
        };
    }
}
