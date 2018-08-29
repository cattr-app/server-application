import {Component, OnInit, Input, EventEmitter, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Router} from '@angular/router';
import { Location } from '@angular/common';
import {AttachedProjectService} from '../../pages/projects/attached-project.service';
import {LocalStorage} from '../../api/storage.model';

@Component({
    selector: 'app-projects-filters',
    templateUrl: './projects.filters.component.html',
})
export class ProjectsFiltersComponent implements OnInit {
    @Input() projectId: any = null;
    @Output() projectIdChange = new EventEmitter();
    projects: Array<any> = [];
    isAuthorized = false;
    selectProjects: any = [];

    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
        protected attachedProjectsService: AttachedProjectService,
    ) {
        this.isAuthorized = apiService.isAuthorized();
    }

    ngOnInit(): void {
        this.attachedProjectsService.subscribeOnUpdate(this.onProjectUpdate.bind(this));
        this.attachedProjectsService.updateAttachedList();
        let currentFilter = LocalStorage.getStorage().get(`filterByProjectIN${ window.location.pathname }`);
        if (currentFilter === null) {
          LocalStorage.getStorage().set(`filterByProjectIN${ window.location.pathname }`, new Array())
        } else {
          this.selectSavedFilters(currentFilter);
        }
    }

    onProjectUpdate(projects) {
        this.updateItems(projects);
    }

    onChange($event) {
        if ($event.length > 0) {
            this.projectId = $event.map(function(user) {
               return user.id;
            });
        } else {
            this.projectId = null;
        }
        this.projectIdChange.emit(this.projectId);

        let currentFilter = LocalStorage.getStorage().get(`filterByProjectIN${ window.location.pathname }`);
        if (this.projectId !== null) {
            // element was removed from filter?
            if (currentFilter.length > this.projectId.length) {
                var diff = this.diffArrays(currentFilter, this.projectId)[0];
                var index = currentFilter.indexOf(diff);
                if (index !== -1) {
                    currentFilter.splice(index, 1); //remove
                }
            }
            currentFilter = new Set(currentFilter);
            this.projectId.forEach(element => {
                currentFilter.add(element);
            });
            currentFilter = Array.from(currentFilter);
        } else {
            currentFilter = new Array();
        }
        LocalStorage.getStorage().set(`filterByProjectIN${ window.location.pathname }`, currentFilter);
    }

    updateItems(items): void {
        if (!this.isAuthorized) {
            this.projects = [];
            return;
        }
        this.projects = items;
    }

    selectSavedFilters(currentFilter) {
        var filtered = [];
        for (var i = 0; i < this.projects.length; i++) {
          for (var j = 0; j < currentFilter.length; j++) {
              if (this.projects[i].id == currentFilter[j]) {
                filtered.push(this.projects[i]);
              }
          }
        }
        for(i = 0; i < filtered.length; i++) { 
          this.projectIdChange.emit(filtered[i].id);
        }
        this.selectProjects = filtered;
      }
  
      // helper
      diffArrays (array1, array2) {
  
          var a = [], diffArray = [];
  
          for (var i = 0; i < array1.length; i++) {
              a[array1[i]] = true;
          }
  
          for (var i = 0; i < array2.length; i++) {
              if (a[array2[i]]) {
                  delete a[array2[i]];
              } else {
                  a[array2[i]] = true;
              }
          }
  
          for (var k in a) {
              diffArray.push(+k);
          }
  
          return diffArray;
      }
}
