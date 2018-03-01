import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TasksService} from "../../tasks/tasks.service";
import {Task} from "../../../models/task.model";
import {BsModalService} from 'ngx-bootstrap/modal';
import {BsModalRef} from 'ngx-bootstrap/modal/bs-modal-ref.service';

@Component({
    selector: 'app-tasks-list',
    templateUrl: './tasks.list.component.html',
    styleUrls: ['./tasks.list.component.scss']
})
export class TasksListComponent implements OnInit {

    tasksArray: Task[] = [];

    modalRef: BsModalRef;

    taskIdForRemoving = 0;

    constructor(private api: ApiService,
                private taskService: TasksService,
                private modalService: BsModalService,) { }

    ngOnInit() {
        this.taskService.getTasks(this.setTasks.bind(this));
    }

    setTasks(result) {
        this.tasksArray = result;
    }

    removeTask() {
        this.taskService.removeTask(this.taskIdForRemoving, this.removeTaskCallback.bind(this));
        this.modalRef.hide();
    }

    openRemoveTaskModalWindow(template: TemplateRef<any>,taskId) {
        this.taskIdForRemoving = taskId;
        this.modalRef = this.modalService.show(template);
    }

    removeTaskCallback(result) {
        location.reload();
    }

}
