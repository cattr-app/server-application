import { Component, IterableDiffers, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { Router } from '@angular/router';
import { DualListComponent } from 'angular-dual-listbox';
import { Role } from '../../../models/role.model';
import { Message } from 'primeng/components/common/api';
import {ItemsCreateComponent} from '../../items.create.component';
import { UsersService } from '../../users/users.service';
import { ApiService } from '../../../api/api.service';
import { RolesService } from '../roles.service';
import { AllowedActionsService } from '../allowed-actions.service';
import { RulesService } from '../rules.service';


@Component({
    selector: 'app-roles-create',
    templateUrl: './roles.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesCreateComponent extends ItemsCreateComponent implements OnInit {

    msgs: Message[] = [];
    public item: Role = new Role();
    sourceRules: any = [];
    confirmedRules: any = [];
    key = 'id';
    displayRules: any = 'name';
    keepSorted = true;
    filter = true;
    height = '250px';
    format: any = DualListComponent.DEFAULT_FORMAT;
    differRules: any;

    constructor(api: ApiService,
                roleService: RolesService,
                router: Router,
                protected roleServ: RolesService,
                protected myRouter: Router,
                allowedService: AllowedActionsService,
                translate: TranslateService,
                protected usersService: UsersService,
                protected ruleService: RulesService,
                differs: IterableDiffers) {
        super(api, roleService, router, allowedService);
        this.differRules = differs.find([]).create(null);

        translate.get('control.add').subscribe((res: string) => { this.format.add = res});
        translate.get('control.remove').subscribe((res: string) => { this.format.remove = res});
        translate.get('control.all').subscribe((res: string) => { this.format.all = res});
        translate.get('control.none').subscribe((res: string) => { this.format.none = res});
    }

    prepareData() {
        return {
            'name': this.item.name,
        };
    }

    ngOnInit() {
        super.ngOnInit();
        this.ruleService.getActions(this.ActionsUpdate.bind(this));
    }

    onSubmit() {
        super.onSubmit();
    }

    createCallback(result) {
        const id = result.res.id;
        const rules = [];
        const RulesChanges = this.differRules.diff(this.confirmedRules);

        if (RulesChanges) {
            RulesChanges.forEachAddedItem((record) => {
                record.item.allow = 1;
                rules.push(record.item);
            });
            RulesChanges.forEachRemovedItem((record) => {
                record.item.allow = 0;
                rules.push(record.item);
            });
        }

        if (rules.length > 0) {
            this.ruleService.editItems(id, rules, this.editBulkCallback.bind(this));
        }
    }

    editBulkCallback(result) {
        console.log('Rules update');
        this.myRouter.navigateByUrl(this.roleServ.getApiPath() + '/list');
    }

    ActionsUpdate(result) {
        for (const item of result) {
            item['id'] = this.sourceRules.length;
            this.sourceRules.push(item);
        }
    }
}
