<template>
    <div class="content-wrapper">
        <div class="container">
            <div class="at-container crud__content">
                <at-steps :current="currentStep" class="steps col-lg-offset-4">
                    <at-step
                        :title="$t('reset.step', { n: 1 })"
                        :description="$t('reset.step_description.step_1')"
                    ></at-step>
                    <at-step
                        :title="$t('reset.step', { n: 2 })"
                        :description="$t('reset.step_description.step_2')"
                    ></at-step>
                    <at-step
                        :title="$t('reset.step', { n: 3 })"
                        :description="$t('reset.step_description.step_3')"
                    ></at-step>
                    <at-step
                        :title="$t('reset.step', { n: 4 })"
                        :description="$t('reset.step_description.step_4')"
                    ></at-step>
                </at-steps>

                <div class="row">
                    <div v-if="currentStep == 0" class="col-6 col-offset-9">
                        <validation-observer v-slot="{ invalid }">
                            <div class="header-text">
                                <h2 class="header-text__title">
                                    {{ $t('reset.tabs.enter_email.title') }}
                                </h2>
                                <p class="header-text__subtitle">
                                    {{ $t('reset.tabs.enter_email.subtitle') }}
                                </p>
                            </div>
                            <validation-provider v-slot="{ errors }" rules="required|email">
                                <small>E-Mail</small>
                                <at-input
                                    v-model="email"
                                    name="login"
                                    :status="errors.length > 0 ? 'error' : ''"
                                    placeholder="E-Mail"
                                    icon="mail"
                                    type="text"
                                    :disabled="disabledForm"
                                >
                                </at-input>
                            </validation-provider>
                            <at-button
                                class="btn"
                                native-type="submit"
                                type="primary"
                                :disabled="invalid || disabledForm"
                                @click="resetPassword"
                                >{{ $t('reset.reset_password') }}</at-button
                            >
                        </validation-observer>
                    </div>

                    <div v-if="currentStep == 1" class="col-6 col-offset-9">
                        <div class="header-text">
                            <i class="icon icon-mail"></i>
                            <h2 class="header-text__title">
                                {{ $t('reset.tabs.check_email.title') }}
                            </h2>
                            <p class="header-text__subtitle">
                                {{ $t('reset.tabs.check_email.subtitle') }}
                            </p>
                        </div>
                    </div>

                    <div v-if="currentStep == 2" class="col-6 col-offset-9">
                        <validation-observer v-if="isValidToken" ref="form" v-slot="{ invalid }">
                            <div class="header-text">
                                <h2 class="header-text__title">
                                    {{ $t('reset.tabs.new_password.title') }}
                                </h2>
                                <p class="header-text__subtitle">
                                    {{ $t('reset.tabs.new_password.subtitle') }}
                                </p>
                            </div>
                            <validation-provider
                                v-slot="{ errors }"
                                rules="required|min:6"
                                :name="$t('field.password')"
                                vid="password"
                            >
                                <small>{{ $t('field.password') }}</small>
                                <at-input
                                    v-model="password"
                                    :name="$t('field.password')"
                                    :status="errors.length > 0 ? 'error' : ''"
                                    :placeholder="$t('field.password')"
                                    icon="lock"
                                    type="password"
                                    :disabled="disabledForm"
                                >
                                </at-input>
                                <p class="error-message">
                                    <small>{{ errors[0] }}</small>
                                </p>
                            </validation-provider>
                            <validation-provider
                                v-slot="{ errors }"
                                rules="required|min:6|confirmed:password"
                                :name="$t('reset.confirm_password')"
                                vid="passwordConfirmation"
                            >
                                <small>{{ $t('reset.confirm_password') }}</small>
                                <at-input
                                    v-model="passwordConfirmation"
                                    name="passwordConfirmation"
                                    :status="errors.length > 0 ? 'error' : ''"
                                    :placeholder="$t('reset.confirm_password')"
                                    icon="lock"
                                    type="password"
                                    :disabled="disabledForm"
                                >
                                </at-input>
                                <p class="error-message">
                                    <small>{{ errors[0] }}</small>
                                </p>
                            </validation-provider>
                            <at-button
                                class="btn"
                                native-type="submit"
                                type="primary"
                                :disabled="invalid || disabledForm"
                                @click="submitNewPassword"
                                >{{ $t('control.submit') }}</at-button
                            >
                        </validation-observer>
                        <div v-else>
                            <div class="header-text">
                                <h2 class="header-text__title">
                                    {{ $t('reset.page_is_not_available') }}
                                </h2>
                                <router-link to="/auth/login">{{ $t('reset.go_away') }}</router-link>
                            </div>
                        </div>
                    </div>

                    <div v-if="currentStep == 3" class="col-6 col-offset-9">
                        <div class="header-text">
                            <i class="icon icon-check"></i>
                            <h2 class="header-text__title">
                                {{ $t('reset.tabs.success.title') }}
                            </h2>
                            <p class="header-text__subtitle"></p>
                            <router-link to="/auth/login">{{ $t('reset.go_away') }}</router-link>
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
    import AuthService from '@/services/auth.service';

    export default {
        name: 'ResetPassword',
        components: {
            ValidationProvider,
            ValidationObserver,
        },
        created() {
            if (this.$route.query.token && this.$route.query.email) {
                this.currentStep = 2;
                this.validateToken();
            }
        },
        data() {
            return {
                email: null,
                password: null,
                passwordConfirmation: null,
                currentStep: 0,
                disabledForm: false,
                isValidToken: true,
                authService: new AuthService(),
            };
        },
        methods: {
            async resetPassword() {
                this.disabledForm = true;

                const payload = {
                    email: this.email,
                };

                try {
                    await this.authService.resetPasswordRequest(payload);
                    this.currentStep = 1;
                } catch (e) {
                    //
                } finally {
                    this.disabledForm = false;
                }
            },
            async validateToken() {
                const payload = {
                    email: this.$route.query.email,
                    token: this.$route.query.token,
                };

                try {
                    await this.authService.resetPasswordValidateToken(payload);
                    this.isValidToken = true;
                } catch (e) {
                    this.isValidToken = false;
                }
            },
            async submitNewPassword() {
                this.disabledForm = true;

                const payload = {
                    email: this.$route.query.email,
                    token: this.$route.query.token,
                    password: this.password,
                    password_confirmation: this.passwordConfirmation,
                };

                try {
                    await this.authService.resetPasswordProcess(payload);
                    this.currentStep = 3;
                    this.disabledForm = false;
                } catch (e) {
                    //
                } finally {
                    this.disabledForm = false;
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .steps {
        margin-bottom: 1.5rem;
    }

    .header-text {
        text-align: center;

        &__title {
            margin-bottom: 1rem;
        }

        &__subtitle {
            margin-bottom: 1rem;
        }
    }

    .icon {
        margin-bottom: 1rem;
        font-size: 92px;

        &-mail {
            color: $blue-2;
        }

        &-check {
            color: $green-1;
        }
    }

    .error-message {
        margin-bottom: 1rem;
    }

    .btn {
        width: 100%;
    }

    .at-input {
        margin-bottom: 0.75rem;
    }
</style>
