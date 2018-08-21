import {Component, OnInit} from '@angular/core';
import { ApiService } from '../../api/api.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  public userIsManager: boolean = false;

  constructor(private api: ApiService) { }

  ngOnInit() {
    const managerRoles = [1, 5];
    const user = this.api.getUser();
    this.userIsManager = managerRoles.indexOf(user.role_id) !== -1;
  }
}
