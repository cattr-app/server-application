import {OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {ActivatedRoute} from '@angular/router';
import {Item} from '../models/item.model';
import {ItemsService} from './items.service';
import {AllowedActionsService} from './roles/allowed-actions.service';

export abstract class ItemsShowComponent implements OnInit {
    id: number;
    protected sub: any;
    public item: Item;

    constructor(protected api: ApiService,
                protected itemService: ItemsService,
                protected router: ActivatedRoute,
                protected allowedAction: AllowedActionsService) {
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this));
    }

    setItem(result) {
        this.item = result;
    }

    can(action: string ): boolean {
        return this.allowedAction.can(action);
    }
}
