import {Component, OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {Router} from "@angular/router";
import {Item} from "../models/item.model";
import {ItemsService} from "./items.service";

export abstract class ItemsCreateComponent implements OnInit {

    public item: Item;

    abstract prepareData();

    constructor(private api: ApiService,
                private itemService: ItemsService,
                private router: Router) {
    }

    ngOnInit() {

    }

    public onSubmit() {
        this.itemService.createItem(
            this.prepareData(),
            this.createCallback.bind(this)
        );
    }

    createCallback(result) {
        console.log(result);
        this.router.navigateByUrl(this.itemService.getApiPath() + '/list');
    }
}
