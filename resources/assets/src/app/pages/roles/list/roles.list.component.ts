import {Component, DoCheck, IterableDiffers, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {RolesService} from '../roles.service';
import {RulesService} from '../rules.service';
import {AllowedActionsService} from '../allowed-actions.service';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {User} from '../../../models/user.model';
import { Subscription } from 'rxjs/Rx';
import {LocalStorage} from '../../../api/storage.model';

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
    requestRoles: Subscription = new Subscription();

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
        let filterByUser = LocalStorage.getStorage().get(`filterByUserIN${ window.location.pathname }`);
        if (filterByUser instanceof Array && filterByUser.length > 0) {
            this.userId = filterByUser;
        }
    }

    ngDoCheck() {
        const changeId = this.differ.diff([this.userId]);

        if (changeId) {
            if (this.requestRoles.closed !== undefined && !this.requestRoles.closed) {
                this.requestRoles.unsubscribe();
            }
            this.requestRoles = this.itemService.getItems(this.setItems.bind(this), this.userId ? {'user_id': ['=', this.userId]} : null);
        }
    }

    UserUpdate() {
        this.user = this.api.getUser();
    }
}
