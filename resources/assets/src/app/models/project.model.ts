import {Item} from './item.model';
import {User} from './user.model';

export interface ProjectData {
    id: number;
    // company_id?: string;
    name?: string;
    description?: string;
    deleted_at?: string;
    created_at?: string;
    updated_at?: string;
    users?: User[];
}

export class Project extends Item {
    public id: number;
    // public company_id?: string;
    public name?: string;
    public description?: string;
    public deleted_at?: string;
    public created_at?: string;
    public updated_at?: string;
    public users?: User[];

    constructor(data?: ProjectData) {
        super();

        if (data) {
            for (const key in data) {
                this[key] = data[key];
            }
        }
    }
}
