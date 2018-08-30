import {Component, OnInit, ViewChild, AfterViewInit} from '@angular/core';
import { TabsetComponent, TabDirective } from 'ngx-bootstrap';
import { ApiService } from '../../api/api.service';
import { AllowedActionsService } from '../roles/allowed-actions.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, AfterViewInit {
  @ViewChild('tabs') tabs: TabsetComponent;

  userIsManager: boolean = false;
  selectedTab: string = '';

  constructor(
    protected api: ApiService,
    protected allowedAction: AllowedActionsService,
  ) { }

  ngOnInit() {
    this.userIsManager = this.allowedAction.can('dashboard/manager_access');
  }

  ngAfterViewInit() {
    if (this.userIsManager) {
      const tabHeading = localStorage.getItem('dashboard-tab');
      if (tabHeading !== null) {
        const index = this.tabs.tabs.findIndex(tab => tab.heading === tabHeading);
        if (index !== -1) {
          setTimeout(() => {
            this.selectedTab = tabHeading;
            this.tabs.tabs[index].active = true;
          });
        }
      }
    }
  }

  onTabSelect(tab: TabDirective) {
    if (tab.heading !== undefined) {
      this.selectedTab = tab.heading;
      localStorage.setItem('dashboard-tab', this.selectedTab);
    }
  }
}
