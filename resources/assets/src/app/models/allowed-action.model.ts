import {Item} from "./item.model";


export interface ActionData {
    object: string;
    action: string;
}

export class AllowedAction extends Item {
    public object: string;
    public action: string;

    constructor(data?: ActionData) {
        super();

        if (data) {
            for (let key in data) {
                this[key] = data[key];
            }
        }
    }
}
