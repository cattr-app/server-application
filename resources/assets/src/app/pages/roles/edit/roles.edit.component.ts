import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Role} from "../../../models/role.model";
import {Router, ActivatedRoute} from "@angular/router";
import {RolesService} from "../roles.service";
import {ItemsEditComponent} from "../../items.edit.component";

@Component({
    selector: 'app-roles-edit',
    templateUrl: './roles.edit.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesEditComponent extends ItemsEditComponent implements OnInit {

    public item: Role = new Role();

    constructor(api: ApiService,
                roleService: RolesService,
                activatedRoute: ActivatedRoute,
                router: Router) {
        super(api, roleService, activatedRoute, router)
    }

    prepareData() {
        return {
            'name': this.item.name,
        }
    }
}
