import { Component, OnInit } from '@angular/core';
import {Task} from "../../../models/task.model";
import {DashboardService} from "../dashboard.service";

@Component({
  selector: 'dashboard-tasklist',
  templateUrl: './tasks.list.component.html'
})
export class TaskListComponent implements OnInit {
    itemsArray: Task[] = [];



    constructor(protected dashboardService: DashboardService) {
    }


    ngOnInit() {
        this.dashboardService.getTasks(this.setTasks.bind(this));
    }

    setTasks(result) {
        this.itemsArray = result;
    }



}
