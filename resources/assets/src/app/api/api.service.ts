import {EventEmitter, Injectable, Output} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {LocalStorage} from './storage.model';

interface TokenResponse {
    access_token?: string;
    token_type?: string;
    error?: string;
}

interface PingResponse {
    status: string;
    cat: string;
}

@Injectable()
export class ApiService {
    @Output() auth: EventEmitter<boolean> = new EventEmitter<boolean>();

    private token?: string = null;
    private tokenType?: string = null;
    private hasToken = false;

    private storage: LocalStorage = LocalStorage.getStorage();

    constructor(private http: HttpClient) {
        this.token = this.storage.get('token');
        this.tokenType = this.storage.get('tokenType');
        this.hasToken = !!(this.token && this.tokenType);
    }

    public setToken(token?: string, tokenType?: string) {
        this.token = token;
        this.tokenType = tokenType || null;
        this.hasToken = !!(token && tokenType);

        this.storage.set('token', this.token);
        this.storage.set('tokenType', this.tokenType);

        this.auth.emit(this.hasToken);
    }

    public updateToken() {
        const f = function (result: TokenResponse) {
            if (!result.error) {
                this.setToken(result.access_token, result.token_type);
            }
        };

        return this.http.post("/api/auth/refresh", [], {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(f.bind(this));
    }

    public logout(callback = null) {
        callback = callback || function (result) {
            console.log(result);
        };

        return this.http.post("/api/auth/logout", [], {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public ping(callback = null) {
        callback = callback || function (result: PingResponse) {
            console.log(result.cat);
        };

        return this.http.get("/api/auth/ping", {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public send(path, data, callback) {
        return this.http.post(`/api/v1/${path}`, data, {
            headers: new HttpHeaders({
                'Content-Type': 'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public sendFile(path, data, callback) {
        return this.http.post(`/api/v1/${path}`, data, {
            headers: new HttpHeaders({
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public sendSettings(data, callback) {
        return this.http.post(`/redmineintegration/settings`, data, {
            headers: new HttpHeaders({
                'Content-Type': 'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public isGuest() {
        return !this.isAuthorized();
    }

    public isAuthorized() {
        return this.hasToken;
    }

    private getAuthString() {
        return `${this.tokenType} ${this.token}`;
    }
}
