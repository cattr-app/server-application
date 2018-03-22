import {Component, OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {Router,ActivatedRoute} from "@angular/router";
import {ItemsService} from "./items.service";
import {Item} from "../models/item.model";
import {AllowedActionsService} from "./roles/allowed-actions.service";


export abstract class ItemsEditComponent implements OnInit {

    id: number;
    protected sub: any;
    public item: Item;

    abstract prepareData();

    constructor(protected api: ApiService,
                protected itemService: ItemsService,
                protected activatedRoute: ActivatedRoute,
                protected router: Router,
                protected allowedAction: AllowedActionsService,) {
    }

    ngOnInit() {
        this.sub = this.activatedRoute.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.itemService.getItem(this.id, this.setItem.bind(this));
    }

    can(action: string ): boolean {
        return this.allowedAction.can(action);
    }

    public onSubmit() {
        this.itemService.editItem(
            this.id,
            this.prepareData(),
            this.editCallback.bind(this)
        );
    }

    setItem(result) {
        console.log(result);
        this.item = result;
    }

    editCallback(result) {
        console.log("Updated");
        this.router.navigateByUrl(this.itemService.getApiPath() + '/list');
    }
}
