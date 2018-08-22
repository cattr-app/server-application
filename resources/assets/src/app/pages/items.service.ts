import {Injectable} from '@angular/core';
import {ApiService} from '../api/api.service';
import {Item} from '../models/item.model';

@Injectable()
export abstract class ItemsService {

    abstract convertFromApi(itemFromApi);
    abstract getApiPath(action ?: string);

    constructor(protected api: ApiService) {
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

    editItem(id, data, callback, errorCallback ?: null) {
        data.id = id;

        this.api.send(
            this.getApiPath() + '/edit',
            data,
            (result) => {
                callback(result);
            },
            errorCallback ? errorCallback : this.api.errorCallback.bind(this)
        );
    }

    getItem(id, callback, params ?: {}) {
        let item: Item;
        const ItemParams = {...{'id': id}, ...params};

        return this.api.send(
            this.getApiPath() + '/show',
            ItemParams,
            (taskFromApi) => {
                item = this.convertFromApi(taskFromApi);
                callback(item);
            });
    }

    getItems(callback, params ?: any) {
        const itemsArray: Item[] = [];

        return this.api.send(
            this.getApiPath() + '/list',
            params ? params : [],
            (result) => {
                result.forEach((itemFromApi) => {
                    itemsArray.push(this.convertFromApi(itemFromApi));
                });

                callback(itemsArray);
            });
    }

    getItemsViaGet(callback, params ?: any, action ?: string) {
        const itemsArray: Item[] = [];

        return this.api.sendViaGet(
            this.getApiPath(action),
            params || [],
            (result) => {
                try {
                    for (let key in result) {
                       itemsArray.push(this.convertFromApi(result[key])); 
                    }
                } catch (e) {
                    console.error(e.message);
                }

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

