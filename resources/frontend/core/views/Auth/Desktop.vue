<template>
    <div class="login row at-row no-gutter">
        <div class="login__wrap">
            <div class="login__form">
                <div class="box">
                    <div class="top">
                        <div class="static-message">
                            <div class="logo" />
                        </div>
                        <h1 class="login__title">Cattr</h1>
                    </div>
                    <template v-if="error">
                        <div>
                            <at-alert :message="$t('auth.desktop_error')" class="login__error" type="error" />
                        </div>
                        <at-button class="login__btn" type="primary" @click="commonLogin"
                            >{{ $t('auth.switch_to_common') }}
                        </at-button>
                    </template>
                    <template v-else>
                        <at-alert :message="$t('auth.desktop_working')" class="login__error" type="info" />
                    </template>
                </div>
            </div>
            <a class="login__slogan" href="https://cattr.app" v-html="slogan" />
        </div>
        <div class="hero col-16" />
    </div>
</template>

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
            flex-direction: column;
            justify-content: center;
            width: 100%;
        }

        &__form {
            flex: 8;
            width: 100%;
        }

        &__slogan {
            align-content: flex-start;
            color: $gray-3;
            display: flex;
            flex: 1;
            justify-content: center;
            margin: 0;
            width: 100%;
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
            text-align: center;
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
            background: #6159e6 url('../../assets/login.svg') no-repeat;
            background-size: 100%;
            display: flex;
        }
    }
</style>

<script>
    import sloganGenerator from '@/helpers/sloganGenerator';

    export default {
        name: 'desktop-login',
        computed: {
            slogan() {
                return sloganGenerator();
            },
        },
        data() {
            return {
                error: false,
            };
        },
        methods: {
            commonLogin() {
                this.$router.replace({ name: 'auth.login' });
            },
            finish(error = true) {
                this.error = error;
                this.$Loading.error();
            },
        },
        mounted() {
            this.$Loading.start();

            if (location.search.length === 0) {
                this.finish();
            } else {
                const query = location.search.substr(1).split('=');

                if (query[0] !== 'token' && query.length !== 2) {
                    this.finish();
                } else {
                    const apiService = this.$store.getters['user/apiService'];
                    apiService
                        .attemptDesktopLogin(query[1])
                        .then(() => apiService.getCompanyData())
                        .then(() => this.finish(false))
                        .catch(() => this.finish());
                }
            }
        },
    };
</script>
