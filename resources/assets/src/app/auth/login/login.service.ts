import { Injectable } from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {Login} from "./login.model";
import { Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';

@Injectable()
export class LoginService {
    constructor(private http: HttpClient) { }

    public send(data: Login, callback, errorCallback?: Function) {
        return this.http.post("/api/auth/login", data, {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': 'my-auth-token'
            })
        }).pipe(catchError(err => {
            errorCallback && errorCallback(err);
            return Observable.from([]);
        })).subscribe(callback);
    }
}
