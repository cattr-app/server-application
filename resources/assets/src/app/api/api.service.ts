import {EventEmitter, Injectable, Output} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {LocalStorage} from "./storage.model";

@Injectable()
export class ApiService {
    @Output() auth: EventEmitter<boolean> = new EventEmitter<boolean>();

    private token?: string = null;
    private tokenType?: string = null;
    private hasToken: boolean = false;

    private storage: LocalStorage = LocalStorage.getStorage();

    constructor(private http: HttpClient) {
        this.token = this.storage.get("token");
        this.tokenType = this.storage.get("tokenType");
        this.hasToken = !!(this.token && this.tokenType);
    }

    public setToken(token?: string, tokenType?: string) {
        this.token = token;
        this.tokenType = tokenType || null;
        this.hasToken = !!(token && tokenType);

        this.storage.set("token", this.token);
        this.storage.set("tokenType", this.tokenType);

        this.auth.emit(this.hasToken);
    }

    public test(data, callback) {
        return this.http.post("/api/v1/webservice/create", data, {
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
