<template>
    <div class="recaptcha">
        <at-switch
            v-model="recaptchaParams.captcha_enabled"
            class="switch-custom"
            size="large"
            @change="$set(recaptchaParams, 'captcha_enabled', $event)"
        >
            <span slot="checkedText" v-t="'setup.buttons.checked'" />
            <span slot="unCheckedText" v-t="'setup.buttons.unchecked'" />
        </at-switch>
        <template v-if="recaptchaParams.captcha_enabled">
            <validation-observer ref="validate">
                <validation-provider v-slot="{ errors }" name="Site key" rules="required">
                    <h6>Site Key</h6>
                    <at-input
                        v-model="recaptchaParams.site_key"
                        :status="errors.length > 0 ? 'error' : ''"
                        placeholder="Site key"
                        type="text"
                    />
                    <p v-html="errors[0]" />
                </validation-provider>

                <validation-provider v-slot="{ errors }" name="Secret key" rules="required">
                    <h6>Secret Key</h6>
                    <at-input
                        v-model="recaptchaParams.secret_key"
                        :status="errors.length > 0 ? 'error' : ''"
                        placeholder="Secret key"
                        type="text"
                    />
                    <p v-html="errors[0]" />
                </validation-provider>
            </validation-observer>
            <div class="recaptcha__wrap-button">
                <a class="recaptcha__get-key" href="https://www.google.com/recaptcha/admin/create" target="_blank">
                    {{ $t('setup.header.recaptcha.get_recaptcha') }}
                </a>
            </div>
        </template>
    </div>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';

    export default {
        name: 'recaptcha',
        components: { ValidationObserver, ValidationProvider },
        props: {
            storage: {},
        },
        data() {
            return {
                status: 'process',
                recaptchaParams: {
                    captcha_enabled: false,
                    site_key: '',
                    secret_key: '',
                },
            };
        },
        created() {
            this.recaptchaParams = {
                ...this.recaptchaParams,
                ...this.storage,
            };

            this.checkFinishedState();
        },
        methods: {
            checkFinishedState() {
                if (
                    !this.recaptchaParams.captcha_enabled ||
                    (this.recaptchaParams.secret_key && this.recaptchaParams.site_key)
                ) {
                    this.status = 'finish';
                } else {
                    this.status = 'process';
                }

                this.$emit('setStatus', this.status);
            },
        },
        watch: {
            recaptchaParams: {
                handler() {
                    this.checkFinishedState();
                    this.$emit('updateStorage', this.recaptchaParams);
                },
                deep: true,
            },
        },
    };
</script>

<style lang="scss" scoped>
    .recaptcha {
        display: flex;
        flex-direction: column;
        width: 50%;

        &__get-key {
            box-shadow: 0 1px 0 0 #618fe8;
            color: #618fe8;
            cursor: pointer;
            margin-top: 1rem;
        }

        &__wrap-button {
            display: flex;
            justify-content: flex-end;
        }
    }

    .switch-custom {
        align-self: center;
        display: flex;
        margin-bottom: 16px;
        max-width: 120px;
    }
</style>
