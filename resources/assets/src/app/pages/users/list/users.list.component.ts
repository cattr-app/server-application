import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';

import {BsModalService} from 'ngx-bootstrap/modal';
import {UsersService} from "../users.service";
import {ItemsListComponent} from "../../items.list.component";
import {User} from "../../../models/user.model";

@Component({
    selector: 'app-users-list',
    templateUrl: './users.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersListComponent extends ItemsListComponent implements OnInit {

    itemsArray: User[] = [];

    constructor(api: ApiService,
                userService: UsersService,
                modalService: BsModalService) {
        super(api, userService, modalService);
    }

}
