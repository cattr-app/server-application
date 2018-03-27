import {Item} from "./item.model";


export interface ActionData {
    object: string;
    action: string;
    name: string;
}

export class Action extends Item {
    public object: string;
    public action: string;
    public name: string;

    constructor(data?: ActionData) {
        super();

        if (data) {
            for (let key in data) {
                this[key] = data[key];
            }
        }
    }
}
