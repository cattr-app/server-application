import {Component, OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {ItemsService} from "./items.service";
import {Item} from "../models/item.model";


export abstract class ItemsEditComponent implements OnInit {

    id: number;
    protected sub: any;
    public item: Item;

    abstract prepareData();

    constructor(protected api: ApiService,
                protected userService: ItemsService,
                protected router: ActivatedRoute) {
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.userService.getItem(this.id, this.setItem.bind(this));
    }

    public onSubmit() {
        this.userService.editItem(
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
    }
}
