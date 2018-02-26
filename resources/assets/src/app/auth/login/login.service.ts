import { Injectable } from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {Login} from "./login.model";

@Injectable()
export class LoginService {
    constructor(private http: HttpClient) { }

    public send(data: Login, callback) {
        return this.http.post("/api/auth/login", data, {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': 'my-auth-token'
            })
        }).subscribe(callback);
    }
}
