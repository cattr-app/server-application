import { Component, OnInit, ChangeDetectorRef } from "@angular/core";
import { ActivatedRoute, Router } from "@angular/router";

import { RegistrationService, RegistrationFormData } from "../../registration.service";

@Component({
    selector: 'app-register',
    templateUrl: './register.component.html',
    styleUrls: ['./register.component.scss'],
})
export class RegisterComponent implements OnInit {
    error?: string = null;
    loading: boolean = true;
    showForm: boolean = false;
    key: string = null;
    data: RegistrationFormData = null;

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected service: RegistrationService,
        protected cdr: ChangeDetectorRef,
    ) { }

    ngOnInit() {
        this.route.params.subscribe(async params => {
            this.key = params.key;

            try {
                this.loading = true;
                this.data = await this.service.getFormData(this.key);
                this.loading = false;
                this.showForm = true;
                this.cdr.detectChanges();
            } catch (e) {
                console.error(e);
                this.error = 'Registration token is invalid or has expired.';
                this.showForm = false;
            }
        });
    }

    async onSubmit() {
        try {
            await this.service.postFormData(this.key, this.data);
            this.router.navigate(['auth', 'login']);
        } catch (e) {
            console.error(e);

            const message = e.error && e.error.error
                ? e.error.error
                : e.statusText;
            this.error = message;
        }
    }
}
