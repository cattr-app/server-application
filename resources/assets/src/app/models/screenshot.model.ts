import {Item} from "./item.model";

export interface ScreenshotData {
    id: number;
    time_interval_id?: number;
    name?: string;
    path?: string;
    deleted_at?: string;
    created_at?: string;
    updated_at?: string;
}

export class Screenshot extends Item {
    public id: number;
    public time_interval_id?: number;
    public name?: string;
    public path?: string;
    public deleted_at?: string;
    public created_at?: string;
    public updated_at?: string;

    constructor(data?: ScreenshotData) {
        super();

        if (data) {
            for (let key in data) {
                this[key] = data[key];
            }
        }
    }
}
