<template>
    <div class="login row at-row no-gutter">
        <div class="login__wrap">
            <div class="login__form">
                <validation-observer ref="observer" class="box" tag="div" @submit.prevent="submit">
                    <div class="top">
                        <div class="static-message">
                            <div class="logo"></div>
                        </div>
                        <h1 class="login__title">Cattr</h1>
                    </div>
                    <div>
                        <at-alert
                            v-if="error"
                            type="error"
                            class="login__error"
                            closable
                            :message="error"
                            @on-close="error = null"
                        />

                        <component :is="config.authInput" @change="change" @submit="submit" />

                        <vue-recaptcha
                            v-if="recaptchaKey"
                            ref="recaptcha"
                            :loadRecaptchaScript="true"
                            :sitekey="recaptchaKey"
                            class="recaptcha"
                            @verify="onCaptchaVerify"
                            @expired="onCaptchaExpired"
                        ></vue-recaptcha>
                    </div>
                    <at-button
                        class="login__btn"
                        native-type="submit"
                        type="primary"
                        :loading="isLoading"
                        :disabled="isLoading"
                        @click="submit"
                        >{{ $t('auth.submit') }}</at-button
                    >
                    <router-link class="link" to="/auth/password/reset">{{ $t('auth.forgot_password') }}</router-link>
                </validation-observer>
            </div>
            <a class="login__slogan" href="https://cattr.app" v-html="slogan" />
        </div>
        <div class="hero col-16"></div>
    </div>
</template>

<script>
    import { ValidationObserver } from 'vee-validate';
    import { VueRecaptcha } from 'vue-recaptcha';
    import AuthInput from './AuthInput';
    import sloganGenerator from '@/helpers/sloganGenerator';
    import has from 'lodash/has';

    export const config = { authInput: AuthInput };

    export default {
        name: 'Login',

        components: {
            ValidationObserver,
            VueRecaptcha,
            AuthInput,
        },

        data() {
            return {
                user: {
                    email: null,
                    password: null,
                    recaptcha: null,
                },
                recaptchaKey: null,
                error: null,
                isLoading: false,
            };
        },

        computed: {
            config() {
                return config;
            },
            slogan() {
                return sloganGenerator();
            },
        },

        mounted() {
            if (this.$store.getters['user/isLoggedIn']) {
                this.$router.push({ name: 'dashboard' });
            }
        },

        methods: {
            getRandomIntInclusive(min, max) {
                min = Math.ceil(min);
                max = Math.floor(max);
                return Math.floor(Math.random() * (max - min + 1)) + min;
            },

            onCaptchaVerify(response) {
                this.user.recaptcha = response;
            },

            onCaptchaExpired() {
                this.$refs.recaptcha.reset();
            },

            change(user) {
                this.user = { ...this.user, ...user };
            },

            async submit() {
                const valid = await this.$refs.observer.validate();
                if (!valid) {
                    return;
                }

                this.$Loading.start();
                this.isLoading = true;
                const apiService = this.$store.getters['user/apiService'];

                try {
                    if ('grecaptcha' in window) {
                        this.$refs.recaptcha.reset();
                    }

                    await apiService.attemptLogin(this.user);
                    await apiService.getCompanyData();

                    this.error = null;
                    this.$Loading.finish();
                } catch (e) {
                    this.$Loading.error();

                    if (has(e, 'response.status')) {
                        if (e.response.status === 429 && this.recaptchaKey === null) {
                            this.recaptchaKey = e.response.data.info.site_key;
                        }

                        let message;

                        if (e.response.status === 401) {
                            message = this.$t('auth.message.user_not_found');
                        } else if (e.response.status === 429) {
                            message = this.$t('auth.message.solve_captcha');
                        } else if (e.response.status === 503) {
                            message = this.$t('auth.message.data_reset');
                        } else {
                            message = this.$t('auth.message.auth_error');
                        }

                        this.error = message;
                    }
                } finally {
                    this.isLoading = false;
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .login {
        flex-wrap: nowrap;
        height: 100vh;
        margin: 0;
        max-height: 100vh;
        position: relative;
        width: 100%;

        &__wrap {
            align-items: center;
            display: flex;
            width: 100%;
            flex-direction: column;
            justify-content: center;
        }
        &__form {
            width: 100%;
            flex: 8;
        }

        &__slogan {
            flex: 1;
            margin: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-content: flex-start;
            color: $gray-3;
        }

        &__title {
            color: $black-900;
            font-size: 1.8rem;
            text-align: center;
        }

        &__btn {
            margin-bottom: 1rem;
        }

        &__error {
            margin-bottom: 1rem;
            overflow: initial;
        }

        .box {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: center;
            padding: 0 $spacing-08;
            width: 100%;

            .top {
                display: flex;
                flex-flow: column nowrap;
                margin-bottom: $layout-01;

                .static-message {
                    align-items: center;
                    display: flex;
                    flex-flow: column nowrap;

                    .logo {
                        align-items: center;
                        background: url('../../assets/logo.svg');
                        background-size: cover;
                        border-radius: 10px;
                        color: #ffffff;
                        display: flex;
                        font-size: 1.8rem;
                        font-weight: bold;
                        height: 60px;
                        justify-content: center;
                        text-transform: uppercase;
                        width: 60px;
                    }
                }
            }

            .recaptcha {
                margin-bottom: 10px;
            }
        }

        .link {
            color: $blue-1;
            font-weight: 600;
            text-align: center;
        }

        ::v-deep .input-group {
            margin-bottom: 0.75rem;
        }

        .hero {
            background: url('../../assets/login.svg') #6159e6;
            background-repeat: no-repeat;
            background-size: 100%;
            display: flex;
        }
    }
</style>
