import {Component, OnInit, Input, EventEmitter, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Router} from '@angular/router';
import { Location } from '@angular/common';
import {AttachedUsersService} from '../../pages/users/attached-users.service';
import {LocalStorage} from '../../api/storage.model';

@Component({
    selector: 'app-users-filters',
    templateUrl: './users.filters.component.html',
})
export class UsersFiltersComponent implements OnInit {
    @Input() userId: any = null;
    @Output() userIdChange = new EventEmitter();
    user: any;
    users: Array<any> = [];
    isAuthorized = false;
    selectUsers: any = [];

    constructor(
        protected apiService: ApiService,
        protected router: Router,
        protected location: Location,
        protected attachedUsersService: AttachedUsersService,
    ) {
        this.isAuthorized = apiService.isAuthorized();
    }

    ngOnInit(): void {

        this.update(this.apiService.getUser());
        this.attachedUsersService.subscribeOnUpdate(this.onUserUpdate.bind(this));
        this.attachedUsersService.updateAttachedList();
        let currentFilter = LocalStorage.getStorage().get(`filterByUserIN${ window.location.pathname }`);
        if (currentFilter === null) {
          LocalStorage.getStorage().set(`filterByUserIN${ window.location.pathname }`, new Array())
        } else {
          this.selectSavedFilters(currentFilter);
        }
    }

    update(user) {
        this.user = user;
    }

    onUserUpdate(users) {
        this.updateItems();
    }

    onChange($event) {
        if ($event.length > 0) {
            this.userId = $event.map(function(user) {
                return user.id;
            });
        } else {
            this.userId = null;
        }
        this.userIdChange.emit(this.userId);

        let currentFilter = LocalStorage.getStorage().get(`filterByUserIN${ window.location.pathname }`);
        if (this.userId !== null) {
            // element was removed from filter?
            if (currentFilter.length > this.userId.length) {
                var diff = this.diffArrays(currentFilter, this.userId)[0];
                var index = currentFilter.indexOf(diff);
                if (index !== -1) {
                    currentFilter.splice(index, 1); //remove
                }
            }
            currentFilter = new Set(currentFilter);
            this.userId.forEach(element => {
                currentFilter.add(element);
            });
            currentFilter = Array.from(currentFilter);
        } else {
            currentFilter = new Array();
        }
        LocalStorage.getStorage().set(`filterByUserIN${ window.location.pathname }`, currentFilter);
    }

    updateItems(): void {
        if (!this.isAuthorized) {
            this.users = [];
            this.user = [];
            return;
        }
        const user: any = this.user;
        const attachedItems: any = this.attachedUsersService.attachedUsers;
        attachedItems.push(user);

        this.users = attachedItems;
    }

    selectSavedFilters(currentFilter) {
      var filtered = [];
      for (var i = 0; i < this.users.length; i++) {
        for (var j = 0; j < currentFilter.length; j++) {
            if (this.users[i].id == currentFilter[j]) {
              filtered.push(this.users[i]);
            }
        }
      }
      for(i = 0; i < filtered.length; i++) { 
        this.userIdChange.emit(filtered[i].id);
      }
      this.selectUsers = filtered;
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
