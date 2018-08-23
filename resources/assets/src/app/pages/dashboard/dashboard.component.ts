import {Component, OnInit, ViewChild, AfterViewInit} from '@angular/core';
import { TabsetComponent, TabDirective } from 'ngx-bootstrap';
import { ApiService } from '../../api/api.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, AfterViewInit {
  @ViewChild('tabs') tabs: TabsetComponent;

  public userIsManager: boolean = false;

  constructor(private api: ApiService) { }

  ngOnInit() {
    const managerRoles = [1, 5];
    const user = this.api.getUser();
    this.userIsManager = managerRoles.indexOf(user.role_id) !== -1;
  }

  ngAfterViewInit() {
    if (this.userIsManager) {
      const tabHeading = localStorage.getItem('dashboard-tab');
      if (tabHeading) {
        const index = this.tabs.tabs.findIndex(tab => tab.heading === tabHeading);
        if (index !== -1) {
          setTimeout(() => this.tabs.tabs[index].active = true);
        }
      }
    }
  }

  onTabSelect(tab: TabDirective) {
    localStorage.setItem('dashboard-tab', tab.heading);
  }
}
