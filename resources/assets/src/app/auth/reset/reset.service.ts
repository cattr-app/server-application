import { Injectable } from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';

import { Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';

import {Reset} from "./reset.model";

@Injectable()
export class ResetService {
    constructor(private http: HttpClient) { }

    public send(data: Reset, callback, errorCallback?: Function) {
        return this.http.post("/api/auth/send-reset", data, {
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
