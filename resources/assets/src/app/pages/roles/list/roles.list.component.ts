import {Component, DoCheck, IterableDiffers, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {RolesService} from '../roles.service';
import {RulesService} from '../rules.service';
import {AllowedActionsService} from '../allowed-actions.service';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {User} from '../../../models/user.model';
import { Subscription } from 'rxjs/Rx';

@Component({
    selector: 'app-roles-list',
    templateUrl: './roles.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesListComponent extends ItemsListComponent implements OnInit, DoCheck {
    user: User;
    p = 1;
    userId: any = '';
    differ: any;
    request: Subscription = new Subscription();

    constructor(
        api: ApiService,
        roleService: RolesService,
        modalService: BsModalService,
        protected ruleService: RulesService,
        allowedService: AllowedActionsService,
        differs: IterableDiffers,
    ) {
        super(api, roleService, modalService, allowedService);
        this.differ = differs.find([]).create(null);
    }

    ngOnInit() {
        this.UserUpdate();
    }

    ngDoCheck() {
        const changeId = this.differ.diff([this.userId]);

        if (changeId) {
            if (this.request.closed !== undefined && !this.request.closed) {
                this.request.unsubscribe();
            }
            this.request = this.itemService.getItems(this.setItems.bind(this), this.userId ? {'user_id': ['=', this.userId]} : null);
        }
    }

    UserUpdate() {
        this.user = this.api.getUser();
    }
}
