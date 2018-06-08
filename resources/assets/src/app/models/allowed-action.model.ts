import {Item} from './item.model';


export interface ActionData {
    object: string;
    action: string;
    name: string;
}

export class AllowedAction extends Item {
    public object: string;
    public action: string;
    public name: string;

    constructor(data?: ActionData) {
        super();

        if (data) {
            for (const key in data) {
                this[key] = data[key];
            }
        }
    }
}
