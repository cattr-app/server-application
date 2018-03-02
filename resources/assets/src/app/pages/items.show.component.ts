import {Component, OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {Item} from "../models/item.model";
import {ItemsService} from "./items.service";

export abstract class ItemsShowComponent implements OnInit {
    id: number;
    private sub: any;
    public item: Item;

    constructor(private api: ApiService,
                private itemService: ItemsService,
                private router: ActivatedRoute) {
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
}
