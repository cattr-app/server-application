import {Item} from "./item.model";


interface RuleData {
    id: number;
    rule_id: number;
    object: string;
    action: string;
    allow: boolean;
}

export class Rule extends Item {
    public id: number;
    public rule_id: number;
    public object: string;
    public action: string;
    public allow: boolean;

    constructor(data?: RuleData) {
        super();

        if (data) {
            for (let key in data) {
                this[key] = data[key];
            }
        }
    }
}
