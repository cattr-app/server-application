<template>
    <div class="content-wrapper">
        <div v-if="isTokenValid" class="container">
            <div class="at-container">
                <div class="at-container__inner">
                    <div v-if="isRegisterSuccess">
                        <div class="header-text">
                            <i class="icon icon-check"></i>
                            <h2 class="header-text__title">
                                {{ $t('register.success_title') }}
                            </h2>
                            <p class="header-text__subtitle">{{ $t('register.success_subtitle') }}</p>
                            <router-link to="/auth/login">{{ $t('reset.go_away') }}</router-link>
                        </div>
                    </div>
                    <div v-else class="row">
                        <div class="col-6 col-offset-9">
                            <div class="header-text">
                                <h2 class="header-text__title">
                                    {{ $t('register.title') }}
                                </h2>
                                <p class="header-text__subtitle">
                                    {{ $t('register.subtitle') }}
                                </p>
                            </div>
                            <at-alert
                                v-if="errorMessage"
                                type="error"
                                class="alert"
                                closable
                                :message="errorMessage"
                                @on-close="errorMessage = null"
                            />
                            <validation-observer v-slot="{ invalid }">
                                <validation-provider v-slot="{ errors }" rules="required|email" name="E-mail">
                                    <div class="input-group">
                                        <small>E-Mail</small>
                                        <at-input
                                            v-model="email"
                                            name="login"
                                            :status="errors.length > 0 ? 'error' : ''"
                                            placeholder="E-Mail"
                                            icon="mail"
                                            type="text"
                                            disabled="true"
                                        >
                                        </at-input>
                                        <p class="error-message">
                                            <small>{{ errors[0] }}</small>
                                        </p>
                                    </div>
                                </validation-provider>
                                <validation-provider v-slot="{ errors }" rules="required" :name="$t('field.full_name')">
                                    <div class="input-group">
                                        <small>{{ $t('field.full_name') }}</small>
                                        <at-input
                                            v-model="fullName"
                                            name="full_name"
                                            :status="errors.length > 0 ? 'error' : ''"
                                            :placeholder="$t('field.full_name')"
                                            icon="user"
                                            type="text"
                                        >
                                        </at-input>
                                        <p class="error-message">
                                            <small>{{ errors[0] }}</small>
                                        </p>
                                    </div>
                                </validation-provider>
                                <validation-provider
                                    v-slot="{ errors }"
                                    rules="required|min:6"
                                    vid="password"
                                    :name="$t('field.password')"
                                >
                                    <div class="input-group">
                                        <small>{{ $t('field.password') }}</small>
                                        <at-input
                                            v-model="password"
                                            name="password"
                                            :status="errors.length > 0 ? 'error' : ''"
                                            :placeholder="$t('field.full_name')"
                                            icon="lock"
                                            type="password"
                                        >
                                        </at-input>
                                        <p class="error-message">
                                            <small>{{ errors[0] }}</small>
                                        </p>
                                    </div>
                                </validation-provider>
                                <validation-provider
                                    v-slot="{ errors }"
                                    rules="required|min:6|confirmed:password"
                                    :name="$t('reset.confirm_password')"
                                >
                                    <div class="input-group">
                                        <small>{{ $t('reset.confirm_password') }}</small>
                                        <at-input
                                            v-model="passwordConfirmation"
                                            name="passwordConfirmation"
                                            :status="errors.length > 0 ? 'error' : ''"
                                            :placeholder="$t('reset.confirm_password')"
                                            icon="lock"
                                            type="password"
                                        >
                                        </at-input>
                                        <p class="error-message">
                                            <small>{{ errors[0] }}</small>
                                        </p>
                                    </div>
                                </validation-provider>
                                <at-button
                                    class="btn"
                                    native-type="submit"
                                    type="primary"
                                    :disabled="invalid || isLoading"
                                    :loading="isLoading"
                                    @click="register"
                                    >{{ $t('register.register_btn') }}</at-button
                                >
                            </validation-observer>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-wrapper -->
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import axios from 'axios';

    export default {
        name: 'ResetPassword',
        components: {
            ValidationProvider,
            ValidationObserver,
        },
        created() {
            if (this.$route.query.token) {
                this.token = this.$route.query.token;
                this.validateToken();
            } else {
                this.$router.push({ name: 'not-found' });
            }
        },
        data() {
            return {
                email: null,
                password: null,
                passwordConfirmation: null,
                fullName: null,
                token: null,
                isTokenValid: false,
                isLoading: false,
                isRegisterSuccess: false,
                errorMessage: null,
            };
        },
        methods: {
            async validateToken() {
                try {
                    const { data } = await axios.get(`/auth/register/${this.token}`);
                    this.email = data.email;
                    this.isTokenValid = true;
                } catch ({ response }) {
                    if (response.status === 404) {
                        this.$router.replace({ name: 'not-found' });
                    }
                }
            },
            async register() {
                this.isLoading = true;

                const data = {
                    email: this.email,
                    full_name: this.fullName,
                    password: this.password,
                    password_confirmation: this.passwordConfirmation,
                };

                try {
                    await axios.post(`auth/register/${this.token}`, data);

                    this.isRegisterSuccess = true;
                } catch ({ response }) {
                    this.errorMessage = response.data.error;
                } finally {
                    this.isLoading = false;
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .header-text {
        text-align: center;

        &__title {
            margin-bottom: 1rem;
        }

        &__subtitle {
            margin-bottom: 1rem;
        }
    }

    .btn {
        width: 100%;
    }

    .input-group {
        margin-bottom: $layout-01;
    }

    .icon {
        margin-bottom: 1rem;
        font-size: 92px;

        &-check {
            color: $green-1;
        }
    }

    .alert {
        margin-bottom: $layout-01;
    }
</style>
