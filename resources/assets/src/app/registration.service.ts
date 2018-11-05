import { Injectable } from "@angular/core";
import { ApiService } from "./api/api.service";

@Injectable()
export class RegistrationService {
    constructor(protected api: ApiService) {
    }

    sendInvitation(email: string): Promise<any> {
        return new Promise((resolve, reject) => {
            this.api.send(
                'register/create',
                {email},
                resolve,
                reject,
            );
        })
    }
}
