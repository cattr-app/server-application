import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from "../api/api.service";
import {Item} from "../models/item.model";

@Injectable()
export abstract class ItemsService {

    constructor(private api: ApiService) {
    }

    createItem(path, data, callback) {
        this.api.send(
            path + '/create',
            data,
            (result) => {
                callback(result);
            }
        );
    }

    editItem(id, path, data, callback) {
        this.api.send(
            path + '/edit',
            data,
            (result) => {
                callback(result);
            }
        );
    }

    getItem(id, path, callback) {
        let cls = this.getItemClass();
        let item: Item;

        return this.api.send(
            path + '/show',
            {'id': id},
            (taskFromApi) => {
                let item = cls.convertFromApi(taskFromApi);
                callback(item);
            });
    }

    getItems(path, callback) {
        let cls = this.getItemClass();
        console.log(cls);

        let itemsArray: Item[] = [];

        return this.api.send(
            path + '/list',
            [],
            (result) => {
                result.data.forEach(function (taskFromApi) {
                    itemsArray.push(cls.convertFromApi(taskFromApi));
                });

                callback(itemsArray);
            });
    }

    removeItem(id, path, callback) {
        this.api.send(
            path + '/remove',
            {
                'id': id,
            },
            (result) => {
                callback(result);
            }
        );
    }

    abstract getApiPath();
    abstract getItemClass();
}

