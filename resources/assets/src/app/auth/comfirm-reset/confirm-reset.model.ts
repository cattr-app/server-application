export class ConfirmReset {
    constructor(
        public login?: string,
        public token?: string,
        public password?: string,
        public recaptcha?: string,
    ) {}
}
