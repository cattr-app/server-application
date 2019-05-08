import { Injectable } from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import { Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';

import {ConfirmReset} from "./confirm-reset.model";

@Injectable()
export class ConfirmResetService {
    constructor(private http: HttpClient) { }

    public send(data: ConfirmReset, callback, errorCallback?: Function) {
        return this.http.post("/api/auth/reset", data, {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': 'my-auth-token',
            })
        }).pipe(catchError(err => {
            errorCallback && errorCallback(err);
            return Observable.from([]);
        })).subscribe(callback);
    }
}
