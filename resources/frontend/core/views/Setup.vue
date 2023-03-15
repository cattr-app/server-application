<template>
    <div class="content-wrapper">
        <div class="container">
            <div class="at-container crud__content">
                <div class="wrap-steps">
                    <at-steps ref="steps" :current="currentStep" class="wrap-steps__steps">
                        <at-step
                            v-for="(component, index) in components"
                            :key="index"
                            :status="getStatus(component.status)"
                            :title="$t('reset.step', { n: ++index })"
                            :description="$t(`setup.step_description.${component.name}`)"
                        />
                    </at-steps>
                </div>

                <div class="col form-wrap">
                    <div class="form-wrap__header">
                        <div class="header-text">
                            <h2 class="header-text__title">
                                {{ $t(`setup.header.${this.components[this.currentStep].name}.title`) }}
                            </h2>
                            <p class="header-text__subtitle">
                                {{ $t(`setup.header.${this.components[this.currentStep].name}.subtitle`) }}
                            </p>
                        </div>
                    </div>
                    <div class="form-wrap__component">
                        <component
                            :is="components[currentStep].component"
                            :storage="components[currentStep].storage"
                            @setStatus="$set(components[currentStep], 'status', $event)"
                            @updateStorage="$set(components[currentStep], 'storage', $event)"
                        />
                    </div>
                </div>

                <div class="wrap-buttons" :style="currentStep === 0 ? 'justify-content: flex-end' : ''">
                    <at-button
                        v-if="currentStep !== 0"
                        type="primary"
                        class="wrap-buttons__button"
                        @click="changeStep(-1)"
                    >
                        {{ $t('setup.buttons.back') }}
                    </at-button>
                    <at-button
                        v-if="currentStep !== components.length - 1"
                        type="primary"
                        class="wrap-buttons__button"
                        :disabled="isNextStepDisabled"
                        @click="changeStep(1)"
                    >
                        {{ $t('setup.buttons.next') }}
                    </at-button>
                    <at-button v-else type="success" :disabled="isNextStepDisabled" @click="completeSetup">
                        {{ $t('setup.buttons.complete') }}
                    </at-button>
                </div>
            </div>
        </div>
        <at-modal
            v-model="installationIsInProgress"
            :title="$t('setup.process.title')"
            :show-close="false"
            :mask-closable="false"
        >
            <h4 v-t="'setup.process.subtitle'" />
            <br />
            <div v-if="!docker" class="congratulation__additional-config">
                <at-alert
                    class="congratulation__title"
                    :message="$t('setup.process.important_information')"
                    :description="$t('setup.process.info_without_docker')"
                    type="warning"
                />
                <br />
                <div class="congratulation__docker-supervisor">
                    <h4>{{ $t('setup.process.title_supervisor') }}</h4>
                    <pre>
[program:cattr-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /app/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/app/backend/storage/logs/worker.log</pre
                    >
                </div>
                <br />
                <div class="congratulation__docker-cron">
                    <h4>{{ $t('setup.process.title_cron') }}</h4>
                    <pre>
* * * * * su www-data -c "php /app/backend/artisan schedule:run" -s /bin/sh >> /dev/null 2>&1</pre
                    >
                </div>
            </div>
            <template #footer>
                <div class="congratulation__modal-footer">
                    <at-checkbox v-if="!docker" v-model="configConfirmed">
                        {{ $t('setup.process.config_confirmation') }}
                    </at-checkbox>
                    <at-button
                        :loading="!setupFinished"
                        :disabled="!setupFinished || (!docker && !configConfirmed)"
                        @click="$router.push('/')"
                    >
                        {{ $t(setupFinished ? 'setup.process.end_install' : 'setup.process.button_process') }}
                    </at-button>
                </div>
            </template>
        </at-modal>
    </div>
</template>

<script>
    import ApiService from '@/services/api';

    export default {
        name: 'Setup',
        data() {
            return {
                docker: process.env.VUE_APP_DOCKER_VERSION !== 'undefined',
                installationIsInProgress: false,
                configConfirmed: false,
                setupFinished: false,
                currentStep: 0,
                components: [
                    {
                        status: 'wait',
                        name: 'welcome',
                        component: () => import(/* webpackChunkName: "setup/welcome" */ './Setup/Welcome'),
                        storage: {},
                    },
                    {
                        status: 'wait',
                        name: 'backend_ping',
                        component: () => import(/* webpackChunkName: "setup/backend_ping" */ './Setup/BackendPing'),
                        storage: null,
                    },
                    {
                        status: 'wait',
                        name: 'company_settings',
                        component: () =>
                            import(/* webpackChunkName: "setup/company_settings" */ './Setup/CompanySettings'),
                        storage: {},
                    },
                    {
                        status: 'wait',
                        name: 'mail_settings',
                        component: () => import(/* webpackChunkName: "setup/mail_settings" */ './Setup/MailSettings'),
                        storage: {},
                    },
                    {
                        status: 'wait',
                        name: 'database_settings',
                        component: () =>
                            import(/* webpackChunkName: "setup/database_settings" */ './Setup/DatabaseSettings'),
                        storage: {},
                    },
                    {
                        status: 'wait',
                        name: 'account',
                        component: () => import(/* webpackChunkName: "setup/account" */ './Setup/Account'),
                        storage: {},
                    },
                    {
                        status: 'wait',
                        name: 'recaptcha',
                        component: () => import(/* webpackChunkName: "setup/recaptcha" */ './Setup/Recaptcha'),
                        storage: {},
                    },
                ],
            };
        },
        methods: {
            changeStep(value) {
                this.currentStep += value;

                if (value < 0) {
                    this.components[this.currentStep].status = 'wait';
                }
            },
            getStatus(status) {
                if (status === 'success') return 'finish';
                return status;
            },
            completeSetup() {
                this.installationIsInProgress = true;

                new ApiService()
                    .finishSetup({
                        ...this.components.reduce((acc, el) => {
                            if (typeof el.storage !== 'object') {
                                const result = { ...acc };
                                result[el.name] = el.storage;

                                return result;
                            }

                            return { ...acc, ...el.storage };
                        }, {}),
                        origin: window.location.origin,
                    })
                    .then(() => {
                        this.setupFinished = true;
                    });
            },
        },
        computed: {
            isNextStepDisabled() {
                return ['success', 'finish'].indexOf(this.components[this.currentStep].status) === -1;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .form-wrap {
        margin-bottom: $layout-01;
        .header-text {
            margin-bottom: $layout-01;

            &__title,
            &__subtitle {
                text-align: center;
            }
        }
        &__component,
        &__header {
            display: flex;
            justify-content: center;
        }
    }
    .wrap-steps {
        display: flex;
        justify-content: center;
        margin-bottom: $layout-01;
    }
    .wrap-buttons {
        width: 100%;
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;

        &__button {
            margin: 0 20px;
            width: 100%;
            max-width: 100px;
        }
    }
    .congratulation {
        &__additional-config pre {
            width: 100%;
            overflow: auto;
            background: #222;
            color: #fff;
            padding: 5px;
            border-radius: 3px;
        }

        &__modal-footer {
            display: flex;
            justify-content: space-between;
        }
    }
</style>
