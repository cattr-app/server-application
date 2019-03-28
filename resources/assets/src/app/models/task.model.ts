import {Item} from './item.model';
import {User} from './user.model';
import {Project} from './project.model';

export interface TaskData {
    id: number;
    project_id?: number;
    task_name?: string;
    description?: string;
    active?: number;
    user_id?: number;
    assigned_by?: number;
    url?: string;
    deleted_at?: string;
    created_at?: string;
    updated_at?: string;
    total_time?: string;
    user?: User;
    assigned?: User;
    project?: Project;
    priority?: { id: number, name: string };
    priority_id?: number;
    important?: boolean;
}

export class Task extends Item {
    public id: number;
    public project_id?: number;
    public task_name?: string;
    public description?: string;
    public active?: number;
    public user_id?: number;
    public assigned_by?: number;
    public url?: string;
    public deleted_at?: string;
    public created_at?: string;
    public updated_at?: string;
    public total_time?: string;
    public user?: User;
    public assigned?: User;
    public project?: Project;
    public priority?: { id: number, name: string };
    public priority_id?: number;
    public important?: boolean;

    constructor(data?: TaskData) {
        super();

        if (data) {
            for (let key in data) {
                this[key] = data[key];
            }
        }
    }
}
