import {Component, OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {Router} from "@angular/router";
import {Item} from "../models/item.model";
import {ItemsService} from "./items.service";
import {AllowedActionsService} from "./roles/allowed-actions.service";
import { NgModel } from '@angular/forms';


export abstract class ItemsCreateComponent implements OnInit {

    public item: Item;

    abstract prepareData();

    constructor(protected api: ApiService,
                private itemService: ItemsService,
                private router: Router,
                protected allowedAction: AllowedActionsService,) {
    }

    ngOnInit() {

    }

    can(action: string ): boolean {
        return this.allowedAction.can(action);
    }

    public onSubmit() {
        this.itemService.createItem(
            this.prepareData(),
            this.createCallback.bind(this)
        );
    }

    public createCallback(result) {
        console.log(result);
        this.router.navigateByUrl(this.itemService.getApiPath() + '/list');
    }

    isDisplayError(model: NgModel) : boolean {
        return model.invalid && (model.dirty || model.touched);
    }

    isDisplaySuccess(model: NgModel) : boolean {
        return model.valid && (model.dirty || model.touched);
    }

}
