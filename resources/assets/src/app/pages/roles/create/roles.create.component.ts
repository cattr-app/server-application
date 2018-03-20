import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Role} from "../../../models/role.model";
import {Router} from "@angular/router";
import {RolesService} from "../roles.service";
import {ItemsCreateComponent} from "../../items.create.component";


@Component({
    selector: 'app-roles-create',
    templateUrl: './roles.create.component.html',
    styleUrls: ['../../items.component.scss']
})
export class RolesCreateComponent extends ItemsCreateComponent implements OnInit {

    public item: Role = new Role();

    constructor(api: ApiService,
                roleService: RolesService,
                router: Router) {
        super(api, roleService, router);
    }

    prepareData() {
        return {
            'name': this.item.name,
        }
    }
}
