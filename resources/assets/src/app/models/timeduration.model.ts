import {Item} from './item.model';

export interface TimeDurationData {
    date: Date;
    duration?: number;
    user_id?: number;
}

export class TimeDuration extends Item {
    public date: Date;
    public duration?: number;
    public user_id?: number;

    constructor(data?: TimeDurationData) {
        super();

        if (data) {
            for (const key in data) {
                this[key] = data[key];
            }
        }
    }
}
