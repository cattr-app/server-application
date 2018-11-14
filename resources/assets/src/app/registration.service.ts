import { Injectable } from "@angular/core";
import { HttpClient, HttpHeaders } from "@angular/common/http";

import { ApiService } from "./api/api.service";

export class RegistrationFormData {
    email: string = '';
    fullName: string = '';
    firstName: string = '';
    lastName: string = '';
    password: string = '';
    timeZone: string = '';
}

@Injectable()
export class RegistrationService {
    constructor(
        protected http: HttpClient,
        protected api: ApiService,
    ) { }

    sendInvitation(email: string): Promise<string> {
        return new Promise((resolve, reject) => {
            this.api.send(
                'register/create',
                {email},
                resolve,
                reject,
            );
        });
    }

    getFormData(key: string): Promise<RegistrationFormData> {
        return new Promise((resolve, reject) => {
            this.http.get(`/api/auth/register/${key}`, {
                headers: new HttpHeaders({
                    'Content-Type': 'application/json',
                })
            }).subscribe(resolve, reject);
        });
    }

    postFormData(key: string, data: RegistrationFormData): Promise<any> {
        return new Promise((resolve, reject) => {
            this.http.post(`/api/auth/register/${key}`, data, {
                headers: new HttpHeaders({
                    'Content-Type': 'application/json',
                })
            }).subscribe(resolve, reject);
        });
    }
}
