import {Item} from "./item.model";

export interface TimeIntervalData {
    id: number;
    task_id?: number;
    start_at?: Date;
    end_at?: Date;
    count_mouse?: number;
    count_keyboard?: number;
    deleted_at?: string;
    created_at?: string;
    updated_at?: string;
}

export class TimeInterval extends Item {
    public id: number;
    public task_id?: number;
    public start_at?: Date;
    public end_at?: Date;
    public count_mouse?: number;
    public count_keyboard?: number;
    public deleted_at?: string;
    public created_at?: string;
    public updated_at?: string;

    constructor(data?: TimeIntervalData) {
        super();

        if (data) {
            for (let key in data) {
                this[key] = data[key];
            }
        }
    }
}
