import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';

import {BsModalService} from 'ngx-bootstrap/modal';
import {BsModalRef} from 'ngx-bootstrap/modal/bs-modal-ref.service';
import {User} from "../../../models/user.model";
import {UsersService} from "../users.service";

@Component({
    selector: 'app-users-list',
    templateUrl: './users.list.component.html',
    styleUrls: ['./users.list.component.scss']
})
export class UsersListComponent implements OnInit {

    usersArray: User[] = [];

    modalRef: BsModalRef;

    userIdForRemoving = 0;

    constructor(private api: ApiService,
                private userService: UsersService,
                private modalService: BsModalService) { }

    ngOnInit() {
        this.userService.getUsers(this.setUsers.bind(this));
    }

    setUsers(result) {
        this.usersArray = result;
    }

    removeUser() {
        this.userService.removeUser(this.userIdForRemoving, this.removeUserCallback.bind(this));
        this.modalRef.hide();
    }

    openRemoveUserModalWindow(template: TemplateRef<any>,taskId) {
        this.userIdForRemoving = taskId;
        this.modalRef = this.modalService.show(template);
    }

    removeUserCallback(result) {
        location.reload();
    }

}
