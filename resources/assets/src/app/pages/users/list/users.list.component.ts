import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiService} from '../../../api/api.service';

import {BsModalService} from 'ngx-bootstrap/modal';
import {UsersService} from "../users.service";
import {ItemsListComponent} from "../../items.list.component";
import {User} from "../../../models/user.model";
import {AllowedActionsService} from "../../roles/allowed-actions.service";

@Component({
    selector: 'app-users-list',
    templateUrl: './users.list.component.html',
    styleUrls: ['./users.list.component.scss', '../../items.component.scss']
})
export class UsersListComponent extends ItemsListComponent implements OnInit {
    @ViewChild('loading') loading: any;

    itemsArray: User[] = [];
    scrollHandler: any = null;
    isLoading = false;
    isAllLoaded = false;
    offset = 0;
    chunksize = 25;

    constructor(api: ApiService,
                userService: UsersService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,) {
        super(api, userService, modalService, allowedService);
    }

    ngOnInit() {
        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    loadNext() {
        if (this.isLoading || this.isAllLoaded) {
            return;
        }

        this.isLoading = true;

        const params = {
            'limit': this.chunksize,
            'offset': this.offset,
            'order_by': 'id',
        };

        this.itemService.getItems((result: User[]) => {
            this.setItems(this.itemsArray.concat(result));
            this.offset += this.chunksize;
            this.isLoading = false;
            this.isAllLoaded = !result.length;
        }, params);
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

        if (bottom_scroll_Y_position < block_Y_position) { // loading new users doesn't needs
            return;
        }

        this.loadNext();
    }
}
