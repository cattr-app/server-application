import {Component, DoCheck, IterableDiffers, OnInit, ViewChild, ElementRef, AfterViewInit} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {Project} from '../../../models/project.model';
import {ProjectsService} from '../projects.service';
import {BsModalService} from 'ngx-bootstrap/modal';
import {ItemsListComponent} from '../../items.list.component';
import {AllowedActionsService} from '../../roles/allowed-actions.service';
import { Subscription } from 'rxjs/Rx';
import {LocalStorage} from '../../../api/storage.model';

@Component({
    selector: 'app-projects-list',
    templateUrl: './projects.list.component.html',
    styleUrls: ['./projects.list.component.scss', '../../items.component.scss']
})
export class ProjectsListComponent extends ItemsListComponent implements OnInit, DoCheck {
    @ViewChild('loading') loading: any;

    itemsArray: Project[] = [];
    scrollHandler: any = null;
    isLoading = false;
    isAllLoaded = false;
    offset = 0;
    chunksize = 25;

    userId = null;
    userDiffer: any;

    projectName = '';
    availableProjects = [];
    suggestedProjects = [];
    selectedProjects = [];

    filter: { [key: string]: any } = {};

    requestProjects: Subscription = new Subscription();

    constructor(api: ApiService,
                projectService: ProjectsService,
                modalService: BsModalService,
                allowedService: AllowedActionsService,
                differs: IterableDiffers,
                ) {
        super(api, projectService, modalService, allowedService);
        this.userDiffer = differs.find([]).create(null);
    }

    ngOnInit() {
        let filterByUser = LocalStorage.getStorage().get(`filterByUserIN${ window.location.pathname }`);
        if (filterByUser instanceof Array && filterByUser.length > 0) {
            this.userId = filterByUser;
        }

        this.itemService.getItems(items => {
            this.availableProjects = items;
            this.suggestedProjects = items;
        });

        this.scrollHandler = this.onScrollDown.bind(this);
        window.addEventListener('scroll', this.scrollHandler, false);
        this.loadNext();
    }

    ngOnDestroy() {
        window.removeEventListener('scroll', this.scrollHandler, false);
    }

    loadNext() {
        if (this.isLoading || this.isAllLoaded) {
            return;
        }

        this.isLoading = true;

        const params = {
            ...this.filter,
            'limit': this.chunksize,
            'offset': this.offset,
            'order_by': 'id',
        };

        if (this.requestProjects.closed !== undefined && !this.requestProjects.closed) {
            this.requestProjects.unsubscribe();
        }

        this.requestProjects = this.itemService.getItems((result: Project[]) => {
            this.setItems(this.itemsArray.concat(result));
            this.offset += this.chunksize;
            this.isLoading = false;
            this.isAllLoaded = !result.length;
        }, params);
    }

    reload() {
        this.offset = 0;
        this.isLoading = false;
        this.isAllLoaded = false;
        this.setItems([]);
        this.loadNext();
    }

    onScrollDown() {
        const block_Y_position = this.loading.nativeElement.offsetTop;
        const scroll_Y_top_position = window.scrollY;
        const windowHeight = window.innerHeight;
        const bottom_scroll_Y_position = scroll_Y_top_position + windowHeight;

        if (bottom_scroll_Y_position < block_Y_position) { // loading new users doesn't needs
            return;
        }

        this.loadNext();
    }

    ngDoCheck() {
        const userChanged = this.userDiffer.diff([this.userId]);

        if (userChanged) {
            if (this.userId) {
                this.filter.user_id = ['=', this.userId];
            } else {
                delete this.filter.user_id;
            }

            this.reload();
        }
    }

    searchProject(event) {
        this.suggestedProjects = this.availableProjects.filter(project =>
            project.name.toLowerCase().indexOf(event.query.toLowerCase()) !== -1);
        this.projectName = event.query;
        this.updateProjectName();
    }

    updateProjectName() {
        if (this.projectName.length) {
            this.filter.name = ['like', '%' + this.projectName + '%'];
        } else {
            delete this.filter.name;
        }

        this.reload();
    }

    updateSelectedProjects() {
        if (this.selectedProjects.length) {
            const ids = this.selectedProjects.map(project => project.id);
            this.filter.id = ['=', ids];
        } else {
            delete this.filter.id;
        }

        this.reload();
    }
}
