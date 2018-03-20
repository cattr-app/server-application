import {Item} from "./item.model";
import {Rule} from "./rule.model";



interface RoleData {
    id: number;
    name: string;
    rules: Rule[];
}

export class Role extends Item {
    public id: number;
    public name: string;
    public rules: Rule[];

    constructor(data?: RoleData) {
        super();

        if (data) {
            for (let key in data) {
                this[key] = data[key];
            }
        }
    }
}
