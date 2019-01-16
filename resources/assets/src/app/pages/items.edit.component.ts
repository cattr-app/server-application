import {OnInit} from '@angular/core';
import {ApiService} from '../api/api.service';
import {Router, ActivatedRoute} from '@angular/router';
import {ItemsService} from './items.service';
import {Item} from '../models/item.model';
import {Message} from 'primeng/components/common/api';
import {AllowedActionsService} from './roles/allowed-actions.service';
import { NgModel } from '@angular/forms';

export abstract class ItemsEditComponent implements OnInit {

    id: number;
    protected sub: any;
    public item: Item;
    msgs: Message[] = [];

    abstract prepareData();

    constructor(protected api: ApiService,
                protected itemService: ItemsService,
                protected activatedRoute: ActivatedRoute,
                protected router: Router,
                protected allowedAction: AllowedActionsService, ) {
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
            this.editCallback.bind(this),
            this.errorCallback.bind(this)
        );
    }

    setItem(result) {
        this.item = result;
    }

    errorCallback(result) {
        this.msgs = [];
        this.msgs.push({severity: 'error', summary: result.error.error, detail: result.error.reason});
    }

    editCallback(result) {
        this.msgs = [];
        this.msgs.push({severity: 'success', summary: 'Success Message', detail: 'Item has been updated'});
    }

    editBulkCallback(name, results) {
        const errors = [];
        for (const msg of results.messages) {
            if (msg.error) {
                let reason = '';
                if (Object.keys(msg.reason).length > 0) {
                    Object.keys(msg.reason).forEach(function (element, index) {
                        reason += ' ' + msg.reason[element][0];
                    });
                } else {
                    reason = msg.reason;
                }
                errors.push({'error': msg.error, 'reason': reason});
            }
        }

        if (errors.length > 0) {
            for (const err of errors) {
                this.msgs.push({severity: 'error', summary: name + ' ' + err.error, detail: err.reason});
            }
        } else {
            this.msgs.push({severity: 'success', summary: 'Success', detail:  name + ' has been updated'});
        }
    }

    isDisplayError(model: NgModel) : boolean {
        return model.invalid && (model.dirty || model.touched);
    }

    isDisplaySuccess(model: NgModel) : boolean {
        return model.valid && (model.dirty || model.touched);
    }
}
