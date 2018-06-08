import {Injectable} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {Item} from '../../models/item.model';
import {Project} from '../../models/project.model';
import {ProjectsService} from './projects.service';


type callbackFunc = (actions: Project) => void;


@Injectable()
export class AttachedProjectService {

    attachedProject: Item[] = [];
    callbacks: callbackFunc[] = [];

    constructor(protected api: ApiService,
                protected projectService: ProjectsService) {
    }

    getApiPath() {
        return 'projects';
    }

    subscribeOnUpdate(callback: callbackFunc) {
        this.callbacks.push(callback);
    }

    errorCallback(error) {
        if (error.status === 403 && error.error.reason === 'action is not allowed') {
            this.setupAttachedList([]);
        }
        console.log('attached error');
    }

    convertFromApi(itemFromApi) {
        return itemFromApi;
    }

    updateAttachedList() {
        const storageItems: any = this.api.getAttachedProject() ? this.api.getAttachedProject() : [];

        if (storageItems.length > 0) {
            this.setupAttachedList(storageItems);
        } else {
            this.projectService.getItems(this.setupAttachedList.bind(this), {'user_id': this.api.getUser().id});
        }
    }

    setupAttachedList(items) {
        this.attachedProject = items;
        this.api.setAttachedProjects(items);

        for (let i = 0; i < this.callbacks.length; i++) {
            this.callbacks.pop()(items);
        }
    }
}
