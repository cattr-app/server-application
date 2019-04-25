import { Component, OnInit, OnDestroy, Output, EventEmitter, ViewChild } from '@angular/core';
import { NgSelectComponent } from '@ng-select/ng-select';

import { ProjectsService } from '../pages/projects/projects.service';

import { Project } from '../models/project.model';
import { LocalStorage } from '../api/storage.model';

interface ProjectSelectItem {
    id: number;
    name?: string;
}

@Component({
    selector: 'app-project-selector',
    templateUrl: './project-selector.component.html',
    styleUrls: ['./project-selector.component.scss']
})
export class ProjectSelectorComponent implements OnInit, OnDestroy {
    @ViewChild('select') _select: NgSelectComponent;

    isLoading: boolean = true;
    projectsAvailable: ProjectSelectItem[] = [];
    projectsSelected: ProjectSelectItem[] = [];
    searchFn: Function;

    @Output() loaded = new EventEmitter<Project[]>();
    @Output() added = new EventEmitter<ProjectSelectItem>();
    @Output() removed = new EventEmitter<ProjectSelectItem>();
    @Output() changed = new EventEmitter<ProjectSelectItem[]>();

    constructor(
        protected projectsService: ProjectsService,
    ) {
        this.searchFn = this.search.bind(this);
    }

    fetchProjects() {
        return new Promise<Project[]>(resolve => {
            this.isLoading = true;

            this.projectsService.getItems((projects: Project[]) => {
                projects = projects.sort((a, b) => a.name.localeCompare(b.name));

                // Add 'Select all' item.
                this.projectsAvailable = [{ id: 0, name: 'Select all' }, ...projects];

                this.isLoading = false;
                resolve(projects);
            });
        });
    }

    ngOnInit() {
        this.fetchProjects().then(projects => {
            const savedProjects = LocalStorage.getStorage().get(`filterByProjectIN${window.location.pathname}`);
            if (savedProjects) {
                this.projectsSelected = savedProjects;
            } else {
                this.projectsSelected = projects;
            }
            this.changed.emit(this.projectsSelected);
            this.loaded.emit(this.projectsAvailable);
        });
    }

    select(ids: number[]) {
        this.projectsSelected = this.projectsAvailable.filter(project => ids.includes(+project.id));
        this.changed.emit(this.projectsSelected);
    }

    search(term: string, item: ProjectSelectItem) {
        term = term.toUpperCase();

        if (item.id === 0) {
            // Always show 'select all'.
            return true;
        }

        const name = item.name.toUpperCase();
        return name.indexOf(term) !== -1;
    }

    add(project: ProjectSelectItem) {
        if (+project.id !== 0) {
            // Not emit event for the 'Select all' item.
            this.added.emit(project);
        }
    }

    remove(project: ProjectSelectItem) {
        if (+project.id !== 0) {
            // Not emit event for the 'Select all' item.
            this.removed.emit(project);
        }
    }

    change(projects: ProjectSelectItem[]) {
        if (!projects.find(project => +project.id === 0)) {
            this.changed.emit(projects);
            LocalStorage.getStorage().set(`filterByProjectIN${window.location.pathname}`, projects);
        } else {
            // Handle 'Select all'.
            this.projectsSelected = this.projectsAvailable.filter(project => {
                if (+project.id === 0) {
                    return false;
                }

                const filter = this._select.filterInput.nativeElement.value.toUpperCase();
                const name = project.name.toUpperCase();
                return name.indexOf(filter) !== -1;
            });

            this.changed.emit(this.projectsSelected);
            LocalStorage.getStorage().set(`filterByProjectIN${window.location.pathname}`, this.projectsSelected);
        }
    }


    cleanupParams() : string[] {
        return [
            '_select',
            'isLoading',
            'projectsAvailable',
            'projectsSelected',
            'searchFn',
            'loaded',
            'added',
            'removed',
            'changed',
            'projectsService',
        ];
    }


    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
