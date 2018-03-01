/*import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../api/api.service';
import {ItemsService} from "./items.service";

import {BsModalService} from 'ngx-bootstrap/modal';
import {BsModalRef} from 'ngx-bootstrap/modal/bs-modal-ref.service';
import {Item} from "../models/item.model";

@Component({
    selector: 'app-item-list',
})
export abstract class ItemsListComponent implements OnInit {

    itemsArray: Item[] = [];

    modalRef: BsModalRef;

    itemIdForRemoving = 0;

    constructor(private api: ApiService,
                private itemService: ItemsService,
                private modalService: BsModalService) {
    }

    ngOnInit() {
        this.itemService.getItems(this.setItems.bind(this));
    }

    setItems(result) {
        this.itemsArray = result;
    }

    removeItem() {
        this.itemService.removeTask(this.itemIdForRemoving, this.removeItemCallback.bind(this));
        this.modalRef.hide();
    }

    openRemoveItemModalWindow(template: TemplateRef<any>, itemId) {
        this.itemIdForRemoving = itemId;
        this.modalRef = this.modalService.show(template);
    }

    removeItemCallback(result) {
        location.reload();
    }
}*/
