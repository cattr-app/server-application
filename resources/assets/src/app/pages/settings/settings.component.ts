import {Component, ViewChild, AfterViewInit} from '@angular/core';
import { TabsetComponent, TabDirective } from 'ngx-bootstrap';

import { ApiService } from '../../api/api.service';
import { AllowedActionsService } from '../roles/allowed-actions.service';

import { TimeInterval } from '../../models/timeinterval.model';
import { Project } from '../../models/project.model';
import { Task } from '../../models/task.model';

import { GeneralComponent } from './tabs/settings.tabs.general.component';
import { IntegrationComponent } from './tabs/settings.tabs.integration.component';
import { UserSettingsComponent } from './tabs/settings.tabs.user.component';

@Component({
    selector: 'app-settings',
    templateUrl: './settings.component.html',
    styleUrls: ['./settings.component.css']
})
export class SettingsComponent implements AfterViewInit {
    @ViewChild('tabs') tabs: TabsetComponent;
    @ViewChild('general') general: GeneralComponent;
    @ViewChild('integration') integration: IntegrationComponent;
    @ViewChild('userSettings') userSettings: UserSettingsComponent;

    selectedTab: string = '';

    constructor(
        protected api: ApiService,
    ) { }


    ngAfterViewInit() {
        const tabHeading = localStorage.getItem('settings-tab');
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

    changeTab(tab: TabDirective) {
        if (tab.heading !== undefined) {
            this.selectedTab = tab.heading;
            localStorage.setItem('settings-tab', this.selectedTab);
        }
    }
}
