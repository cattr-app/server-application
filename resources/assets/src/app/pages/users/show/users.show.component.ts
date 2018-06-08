import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from '@angular/router';
import {UsersService} from '../users.service';
import {User} from '../../../models/user.model';
import {ItemsShowComponent} from '../../items.show.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-users-show',
    templateUrl: './users.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersShowComponent extends ItemsShowComponent implements OnInit {

    public item: User = new User();

    constructor(api: ApiService,
                userService: UsersService,
                router: ActivatedRoute,
                allowService: AllowedActionsService
    ) {
        super(api, userService, router, allowService);
    }
}
