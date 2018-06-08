import {EventEmitter, Injectable, Output} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {LocalStorage} from './storage.model';
import {Router} from '@angular/router';
import { Location } from '@angular/common';

interface TokenResponse {
    access_token?: string;
    token_type?: string;
    error?: string;
    user?: any;
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
    private user?: any = null;
    private attached_users?: any = null;
    private attached_projects?: any = null;

    private storage: LocalStorage = LocalStorage.getStorage();

    constructor(private http: HttpClient, private router: Router, protected location: Location) {
        this.token = this.storage.get('token');
        this.tokenType = this.storage.get('tokenType');
        this.hasToken = !!(this.token && this.tokenType);
        this.user = this.storage.get('user');
        this.attached_users = this.storage.get('attached_users');
        this.attached_projects = this.storage.get('attached_projects');
    }

    public setToken(token?: string, tokenType?: string, user?: any) {
        this.token = token;
        this.tokenType = tokenType || null;
        this.hasToken = !!(token && tokenType);
        this.user = user || null;

        this.storage.set('token', this.token);
        this.storage.set('tokenType', this.tokenType);
        this.storage.set('user', this.user);

        this.auth.emit(this.hasToken);
    }

    public setAttachedUsers(attached_users?: any) {
        this.attached_users = attached_users || null;
        this.storage.set('attached_users', this.attached_users);
    }

    public setAttachedProjects(attached_users?: any) {
        this.attached_projects = attached_users || null;
        this.storage.set('attached_projects', this.attached_projects);
    }

    public getUser() {
        return this.storage.get('user');
    }

    public getAttachedUsers() {
        return this.storage.get('attached_users');
    }

    public getAttachedProject() {
        return this.storage.get('attached_projects');
    }

    public updateToken() {
        const f = function (result: TokenResponse) {
            if (!result.error) {
                this.setToken(result.access_token, result.token_type, result.user);
            }
        };

        return this.http.post('/api/auth/refresh', [], {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(f.bind(this));
    }

    public logout(callback = null, errorCallback = this.errorCallback.bind(this)) {
        callback = callback || function (result) {
            console.log(result);
        };

        return this.http.post('/api/auth/logout', [], {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback, errorCallback);
    }

    public ping(callback = null) {
        callback = callback || function (result: PingResponse) {
            console.log(result.cat);
        };

        return this.http.get('/api/auth/ping', {
            headers: new HttpHeaders({
                'Content-Type':  'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public send(path, data, callback, errorCallback = this.errorCallback.bind(this)) {
        return this.http.post(`/api/v1/${path}`, data, {
            headers: new HttpHeaders({
                'Content-Type': 'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback, errorCallback);
    }

    public sendFile(path, data, callback) {
        return this.http.post(`/api/v1/${path}`, data, {
            headers: new HttpHeaders({
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public sendSettings(data, callback) {
        return this.http.post(`/redmineintegration/settings/update`, data, {
            headers: new HttpHeaders({
                'Content-Type': 'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public getSettings(data, callback) {
        return this.http.post(`/redmineintegration/settings/get`, data, {
            headers: new HttpHeaders({
                'Content-Type': 'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public sendSynchronizeProjects(data, callback) {
        return this.http.post(`/redmineintegration/projects/synchronize`, data, {
            headers: new HttpHeaders({
                'Content-Type': 'application/json',
                'Authorization': this.getAuthString()
            })
        }).subscribe(callback);
    }

    public sendSynchronizeTasks(data, callback) {
        return this.http.post(`/redmineintegration/tasks/synchronize`, data, {
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

    public errorCallback(error) {
        if (error.status === 403 && error.error.reason === 'not logined') {
            this.setAttachedUsers(null);
            this.setAttachedProjects(null);
            this.setToken(null);
            this.location.replaceState('/');
            this.router.navigateByUrl('/');
        }
        console.log(error);
    }

    private getAuthString() {
        return `${this.tokenType} ${this.token}`;
    }
}
