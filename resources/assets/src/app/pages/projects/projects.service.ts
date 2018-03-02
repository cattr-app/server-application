import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Project} from "../../models/project.model";
import {ItemsService} from "../items.service";

@Injectable()
export class ProjectsService extends ItemsService {

    constructor(api: ApiService) {
        super(api);
    }

    getApiPath() {
        return 'projects';
    }

    convertFromApi(itemFromApi) {
        return new Project(
            itemFromApi.id,
            itemFromApi.company_id,
            itemFromApi.name,
            itemFromApi.description,
            itemFromApi.deleted_at,
            itemFromApi.created_at,
            itemFromApi.updated_at
        )
    }

}
