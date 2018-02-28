import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../../api/api.service";
import {Project} from "../../models/project.model";

@Injectable()
export class ProjectsService {

    constructor(private api: ApiService) {
    }

    createProject(companyId, name, description, callback) {
        this.api.send(
            'projects/create',
            {
                'company_id': companyId,
                'name': name,
                'description': description
            },
            (result) => {
                callback(result);
            }
        );
    }

    editProject(projectId, companyId, name, description, callback) {
        this.api.send(
            'projects/edit',
            {
                'project_id': projectId,
                'company_id': companyId,
                'name': name,
                'description': description
            },
            (result) => {
                callback(result);
            }
        );
    }

    getProject(projectId, callback) {
        let project: Project;

        return this.api.send(
            'projects/show',
            {'project_id': projectId},
            (projectFromApi) => {
                project = new Project(
                    projectFromApi.id,
                    projectFromApi.company_id,
                    projectFromApi.name,
                    projectFromApi.description,
                    projectFromApi.deleted_at,
                    projectFromApi.created_at,
                    projectFromApi.updated_at
                );

                callback(project);
            });
    }

    getProjects(callback) {
        let projectsArray: Project[] = [];

        return this.api.send(
            'projects/list',
            [],
            (result) => {
                result.data.forEach(function (projectFromApi) {
                    projectsArray.push(new Project(
                        projectFromApi.id,
                        projectFromApi.company_id,
                        projectFromApi.name,
                        projectFromApi.description,
                        projectFromApi.deleted_at,
                        projectFromApi.created_at,
                        projectFromApi.updated_at
                    ));
                });

                callback(projectsArray);
            });
    }

    removeProject(projectId, callback) {
        this.api.send(
            'projects/remove',
            {
                'project_id': projectId,
            },
            (result) => {
                callback(result);
            }
        );
    }

}
