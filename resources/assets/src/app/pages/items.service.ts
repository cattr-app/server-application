import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../api/api.service";
import {Item} from "../models/item.model";

@Injectable()
export abstract class ItemsService {

    abstract convertFromApi(itemFromApi);
    abstract getApiPath();

    constructor(private api: ApiService) {
    }

    createItem(data, callback) {
        this.api.send(
            this.getApiPath() + '/create',
            data,
            (result) => {
                callback(result);
            }
        );
    }

    editItem(id, data, callback) {
        this.api.send(
            this.getApiPath() + '/edit',
            data,
            (result) => {
                callback(result);
            }
        );
    }

    getItem(id, callback) {
        let item: Item;

        return this.api.send(
            this.getApiPath() + '/show',
            {'id': id},
            (taskFromApi) => {
                item = this.convertFromApi(taskFromApi);
                callback(item);
            });
    }

    getItems(callback) {
        let itemsArray: Item[] = [];

        return this.api.send(
            this.getApiPath() + '/list',
            [],
            (result) => {
                result.data.forEach((itemFromApi) => {
                    itemsArray.push(this.convertFromApi(itemFromApi));
                });

                callback(itemsArray);
            });
    }

    removeItem(id, callback) {
        this.api.send(
            this.getApiPath() + '/remove',
            {
                'id': id,
            },
            (result) => {
                callback(result);
            }
        );
    }
}

