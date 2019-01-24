import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Task} from '../../../models/task.model';
import {Router, ActivatedRoute} from '@angular/router';
import {TasksService} from '../tasks.service';
import {ItemsEditComponent} from '../../items.edit.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import {ProjectsService} from '../../projects/projects.service';
import {Project} from '../../../models/project.model';
import {UsersService} from '../../users/users.service';
import {User} from '../../../models/user.model';

@Component({
    selector: 'app-tasks-edit',
    templateUrl: './tasks.edit.component.html',
    styleUrls: ['../../items.component.scss'],
})
export class TasksEditComponent extends ItemsEditComponent implements OnInit {

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
                activatedRoute: ActivatedRoute,
                router: Router,
                allowedService: AllowedActionsService,
                protected projectService: ProjectsService,
                protected userService: UsersService) {
        super(api, taskService, activatedRoute, router, allowedService);
    }

    ngOnInit() {
        super.ngOnInit();
        this.projectService.getItems(this.setProjects.bind(this), this.can('/projects/full_access') ? {} : {'direct_relation': 1});
        this.userService.getItems(this.setUsers.bind(this));
    }

    setItem(result) {
        this.item = result;
        this.selectedProject = this.item.project_id;
        this.selectedUser = this.item.user_id;
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
    }

    prepareData() {
        return {
            'project_id': this.item.project_id,
            'task_name': this.item.task_name,
            'active': this.item.active,
            'user_id': this.item.user_id,
            'assigned_by': this.item.assigned_by,
            'url': this.item.url,
            'priority_id': this.item.priority_id,
            'important': this.item.important,
        };
    }
}
