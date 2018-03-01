import {Component, OnInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute} from "@angular/router";
import {UsersService} from "../users.service";
import {User} from "../../../models/user.model";

@Component({
    selector: 'app-users-show',
    templateUrl: './users.show.component.html',
    styleUrls: ['./users.show.component.scss']
})
export class UsersShowComponent implements OnInit {
    id: number;
    private sub: any;
    public user: User = new User();

    constructor(private api: ApiService,
                private userService: UsersService,
                private router: ActivatedRoute) {
    }

    ngOnInit() {
        this.sub = this.router.params.subscribe(params => {
            this.id = +params['id'];
        });

        this.userService.getUser(this.id, this.setUser.bind(this));
    }

    setUser(result) {
        this.user = result;
    }
}
