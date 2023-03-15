<template>
    <div>
        <validation-observer v-if="!docker" ref="validate" class="database" @keyup.enter="checkConnection">
            <validation-provider v-slot="{ errors }" rules="required" :name="$t(`setup.header.database_settings.host`)">
                <h6 v-t="'setup.header.database_settings.host'" />
                <at-input
                    v-model="databaseForm.db_host"
                    :status="errors.length > 0 ? 'error' : ''"
                    :placeholder="$t(`setup.header.database_settings.host`)"
                    type="text"
                />
                <p v-html="errors[0]" />
            </validation-provider>

            <validation-provider
                v-slot="{ errors }"
                rules="required"
                :name="$t(`setup.header.database_settings.database`)"
            >
                <h6 v-t="'setup.header.database_settings.database'" />
                <at-input
                    v-model="databaseForm.database"
                    :status="errors.length > 0 ? 'error' : ''"
                    :placeholder="$t(`setup.header.database_settings.database`)"
                    type="text"
                />
                <p v-html="errors[0]" />
            </validation-provider>

            <validation-provider
                v-slot="{ errors }"
                rules="required"
                :name="$t(`setup.header.database_settings.username`)"
            >
                <h6 v-t="'setup.header.database_settings.username'" />
                <at-input
                    v-model="databaseForm.db_user"
                    :status="errors.length > 0 ? 'error' : ''"
                    :placeholder="$t(`setup.header.database_settings.username`)"
                    type="text"
                />
                <p v-html="errors[0]" />
            </validation-provider>

            <validation-provider
                v-slot="{ errors }"
                rules="required"
                :name="$t(`setup.header.database_settings.password`)"
            >
                <h6 v-t="'setup.header.database_settings.password'" />
                <at-input
                    v-model="databaseForm.db_password"
                    :status="errors.length > 0 ? 'error' : ''"
                    :placeholder="$t(`setup.header.database_settings.password`)"
                    type="password"
                />
                <p v-html="errors[0]" />
            </validation-provider>

            <div class="status">
                <div class="status__alert">
                    <at-alert v-if="message" :type="status" :message="message" show-icon />
                </div>
                <at-button type="info" @click="checkConnection">
                    {{ $t('setup.buttons.connect') }}
                </at-button>
            </div>
        </validation-observer>
        <div v-else class="docker-notice">
            <h5 v-t="'setup.header.database_settings.docker_title'" />
            <h5 v-t="'setup.header.database_settings.docker_subtitle'" />
        </div>
    </div>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import ApiService from '@/services/api';

    export default {
        name: 'DatabaseSettings',
        components: {
            ValidationProvider,
            ValidationObserver,
        },
        props: {
            storage: {},
        },
        data() {
            return {
                docker: process.env.VUE_APP_DOCKER_VERSION !== 'undefined',
                databaseForm: {
                    db_host: '',
                    database: '',
                    db_user: '',
                    db_password: '',
                },
                disabledForm: false,
                status: 'process',
                message: '',
                isDisabled: false,
            };
        },
        created() {
            this.databaseForm = this.storage;

            this.$emit('setStatus', this.docker ? 'finish' : this.status);
        },
        methods: {
            async checkConnection() {
                if ('validate' in this.$refs) {
                    this.isDisabled = !(await this.$refs.validate.validate());
                }

                if (this.isDisabled) return;

                this.message = this.$t(`setup.header.database_settings.process`);
                this.isDisabled = true;
                try {
                    const { data } = await new ApiService().checkConnectionDatabase(this.databaseForm);

                    this.status = 'success';
                    this.message = this.$t(`setup.header.database_settings.success`);
                    this.$emit('setStorage', this.databaseForm);
                } catch (e) {
                    this.status = 'error';
                    this.message = this.$t(`setup.header.database_settings.error`);
                }
                this.isDisabled = false;

                this.$emit('setStatus', this.status);
            },
        },
        watch: {
            storage(val) {
                this.databaseForm = val;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .database {
        display: flex;
        flex-direction: column;
    }
    .status {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
        &__alert {
            margin-right: 10px;
            min-width: 150px;
        }
    }
    .docker-notice {
        text-align: center;
    }
</style>
