import {Component, OnInit, TemplateRef} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Role} from "../../../models/role.model";
import {Rule} from "../../../models/rule.model";
import {Action} from "../../../models/action.model";
import {RolesService} from "../roles.service";
import {RulesService} from "../rules.service";
import {ActionsService} from "../actions.service";
import {BsModalService} from 'ngx-bootstrap/modal';
import {BsModalRef} from 'ngx-bootstrap/modal/bs-modal-ref.service';
import {Router} from "@angular/router";
import {ItemsListComponent} from "../../items.list.component";
import {Task} from "../../../models/task.model";




@Component({
    selector: 'app-roles-list',
    templateUrl: './roles.list.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesListComponent extends ItemsListComponent implements OnInit {

    actionsArray: Action[] = [];
    actionsService: ActionsService;
    ruleService: RulesService;

    ngOnInit() {
        super.ngOnInit();
        this.actionsService.getItems(this.ActionsUpdate.bind(this));
    }

    ActionsUpdate(result) {
        this.actionsArray = result;
    }

    ruleName(rule: Rule) {


        for(let action of this.actionsArray) {

            if(rule.object == action.object &&
                rule.action == action.action) {
                return action.name;
            }
        }

        return 'loading...';

    }

    public onRuleUpdate(rule: Rule) {
        this.ruleService.editItem(
            rule.id,
            rule,
            this.editCallback.bind(this)
        );
    }


    editCallback(result) {
        console.log("Updated");
    }


    constructor(
        api: ApiService,
        roleService: RolesService,
        ruleService: RulesService,
        actionsService: ActionsService,
        modalService: BsModalService
    ) {
        super(api, roleService, modalService);
        this.actionsService = actionsService;
        this.ruleService = ruleService;
    }
}
