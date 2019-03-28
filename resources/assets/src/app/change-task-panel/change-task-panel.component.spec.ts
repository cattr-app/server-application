import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardService } from '../dashboard.service';
import { ChangeTaskPanelComponent } from './change-task-panel.component';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ApiService } from '../../../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router } from '@angular/router';
import { AppRoutingModule } from '../../../app-routing.module';
import { Location, LocationStrategy, PathLocationStrategy, APP_BASE_HREF } from '@angular/common';
import { AllowedActionsService } from '../../roles/allowed-actions.service';
import { loadAdminStorage, loadUserStorage, loadManagerStorage } from '../../../test-helper/test-helper';
import { TranslateFakeLoader, TranslateLoader, TranslateModule } from '@ngx-translate/core';
import { TabsModule } from 'ngx-bootstrap/tabs';
import { NgxPaginationModule } from 'ngx-pagination';
import { TimeIntervalsService } from '../../timeintervals/timeintervals.service';
import { ProjectsService } from '../../projects/projects.service';
import { TasksService } from '../../tasks/tasks.service';
import { BsModalService, ComponentLoaderFactory, PositioningService, ModalModule } from 'ngx-bootstrap';
import { Project } from '../../../models/project.model';
import { Task } from '../../../models/task.model';
import { By } from '@angular/platform-browser';
import {AutoCompleteModule} from 'primeng/autocomplete';
import moment = require('moment');

class ChangeTaskPanelMockComponent extends ChangeTaskPanelComponent {
    reload() {
        this.selectedProject = new Project();
        this.selectedTask = new Task();
        this.projects = [new Project(), new Project()];
        this.tasks = [new Task(), new Task()];
        this.totalTime = 60 * 1000 * 10 * 6.5;
    }
}
describe('Dashboard tasklist component (Admin, has tasks)', () => {
    let fixture, component;

    beforeEach(async(() => {
        loadAdminStorage();
        TestBed.configureTestingModule({
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }), TabsModule.forRoot(), NgxPaginationModule, AutoCompleteModule, ModalModule,
            ],
            declarations: [ChangeTaskPanelMockComponent,],
            schemas: [NO_ERRORS_SCHEMA],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                DashboardService,
                ApiService,
                TimeIntervalsService,
                ProjectsService,
                TasksService,
                BsModalService,
                ComponentLoaderFactory,
                PositioningService,
            ],
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(ChangeTaskPanelMockComponent);
                component = fixture.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', () => {
        component.reload();
        fixture.detectChanges();
        expect(component).toBeTruthy();
    });

    it('should has delete button', () => {
        component.reload();
        fixture.detectChanges();
        const delBtn = fixture.debugElement.query(By.css("button.change-task-panel__delete"));
        expect(delBtn).not.toBeNull();
    });

    it('should has add new task button', () => {
        component.reload();
        fixture.detectChanges();
        const addNewTaskBtn = fixture.debugElement.query(By.css("button.change-task-panel__add"));
        expect(addNewTaskBtn).not.toBeNull();
    });

    it('should has change project / task button', () => {
        component.reload();
        fixture.detectChanges();
        const changeBtn = fixture.debugElement.query(By.css("button.change-task-panel__change"));
        expect(changeBtn).not.toBeNull();
    });

    it('should has search input', () => {
        component.reload();
        fixture.detectChanges();
        const searchInput = fixture.debugElement.query(By.css("p-autoComplete"));
        expect(searchInput).not.toBeNull();
    });

    it('should be selected time = 1:05', () => {
        component.reload();
        fixture.detectChanges();
        const selectedTimeBlock = fixture.debugElement.query(By.css("span.change-task-panel__selected"));
        expect(selectedTimeBlock.nativeElement.innerHTML).toContain("1:05");
    });

    it('after click by add task should show modal window \'add task\'', () => {
        component.reload();
        fixture.detectChanges();
        const addNewTaskBtn = fixture.debugElement.query(By.css("button.change-task-panel__add"));
        expect(addNewTaskBtn).not.toBeNull();
        let clickEvent = addNewTaskBtn.listeners.filter(event => event.name == 'click');
        expect(clickEvent.length).toBeGreaterThan(0);
        clickEvent = clickEvent.shift();
        clickEvent.callback.call();
        fixture.detectChanges();
        console.log(fixture.debugElement.nativeElement);
    });
});
