import {Item} from './item.model';


export interface ActionData {
    id: number;
    name: string;
    is_active: boolean;
}

export class RedmineStatus extends Item {
    public id: number;
    public name: string;
    public is_active: boolean;

    constructor(data?: ActionData) {
        super();

        if (data) {
            for (const key in data) {
                this[key] = data[key];
            }
        }
    }
}
