import { Component } from "@angular/core";
import { Router } from "@angular/router";

import { Message } from "primeng/components/common/api";

import { RegistrationService } from "../../../registration.service";

@Component({
    selector: 'app-users-invite',
    templateUrl: './users.invite.component.html',
    styleUrls: ['../../items.component.scss']
})
export class UsersInviteComponent {
    email: string = '';
    error?: string = null;
    msgs: Message[] = [];

    constructor(
        protected service: RegistrationService,
    ) { }

    async onSubmit() {
        try {
            await this.service.sendInvitation(this.email);
            this.msgs = [];
            this.msgs.push({
                severity: 'success',
                summary: 'Success',
                detail: `User ${this.email} has been invited`,
            });
        } catch(e) {
            console.error(e);

            const message = e.error && e.error.error
                ? e.error.error
                : e.statusText;

            this.msgs = [];
            this.msgs.push({
                severity: 'error',
                summary: 'Error',
                detail: message,
            });
        }
    }
}
