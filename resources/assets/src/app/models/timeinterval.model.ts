import {Item} from './item.model';
import {Task} from './task.model';
import {User} from './user.model';
import {Screenshot} from './screenshot.model';

export interface TimeIntervalData {
    id: number;
    task_id?: number;
    user_id?: number;
    start_at?: Date;
    end_at?: Date;
    count_mouse?: number;
    count_keyboard?: number;
    deleted_at?: string;
    created_at?: string;
    updated_at?: string;
    task?: Task;
    user?: User;
    screenshots?: Screenshot[];
}

export class TimeInterval extends Item {
    public id: number;
    public task_id?: number;
    public user_id?: number;
    public start_at?: Date;
    public end_at?: Date;
    public count_mouse?: number;
    public count_keyboard?: number;
    public deleted_at?: string;
    public created_at?: string;
    public updated_at?: string;
    public task?: Task;
    public user?: User;
    public screenshots?: Screenshot[];

    constructor(data?: TimeIntervalData) {
        super();

        if (data) {
            for (const key in data) {
                this[key] = data[key];
            }
        }
    }
}
