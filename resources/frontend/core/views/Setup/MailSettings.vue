<template>
    <div>
        <validation-observer ref="validate">
            <validation-provider
                v-slot="{ errors }"
                rules="required|email"
                :name="$t(`setup.header.mail_settings.email`)"
            >
                <h6 v-t="'setup.header.mail_settings.email'" />
                <at-input
                    v-model="settingsMail.mail_address"
                    :status="errors.length > 0 ? 'error' : ''"
                    placeholder="example@mail.com"
                    type="text"
                />
                <p v-html="errors[0]" />
            </validation-provider>

            <validation-provider v-slot="{ errors }" rules="required" :name="$t(`setup.header.mail_settings.password`)">
                <h6 v-t="'setup.header.mail_settings.password'" />
                <at-input v-model="settingsMail.mail_pass" :status="errors.length > 0 ? 'error' : ''" type="password" />
                <p v-html="errors[0]" />
            </validation-provider>

            <validation-provider v-slot="{ errors }" rules="required" :name="$t(`setup.header.mail_settings.host`)">
                <h6 v-t="'setup.header.mail_settings.host'" />
                <at-input
                    v-model="settingsMail.mail_host"
                    :status="errors.length > 0 ? 'error' : ''"
                    placeholder="smtp.gmail.com"
                    type="text"
                />
                <p v-html="errors[0]" />
            </validation-provider>
            <div class="row-inputs">
                <validation-provider
                    v-slot="{ errors }"
                    rules="required|max:4|numeric"
                    :name="$t(`setup.header.mail_settings.port`)"
                >
                    <div class="port">
                        <h6 v-t="'setup.header.mail_settings.port'" />
                        <at-input
                            v-model="settingsMail.mail_port"
                            :status="errors.length > 0 ? 'error' : ''"
                            placeholder="465"
                        />
                        <p v-html="errors[0]" />
                    </div>
                </validation-provider>

                <validation-provider
                    v-slot="{ errors }"
                    rules="required"
                    :name="$t(`setup.header.mail_settings.encryption`)"
                >
                    <div class="encryption">
                        <h6 v-t="'setup.header.mail_settings.encryption'" />
                        <at-radio-group
                            v-model="settingsMail.encryption"
                            :status="errors.length > 0 ? 'error' : ''"
                            size="large"
                        >
                            <at-radio-button label="false">None</at-radio-button>
                            <at-radio-button label="true">STARTTLS/TLS</at-radio-button>
                        </at-radio-group>
                        <p v-html="errors[0]" />
                    </div>
                </validation-provider>
            </div>
        </validation-observer>
    </div>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    export default {
        name: 'MailSettings',
        components: {
            ValidationProvider,
            ValidationObserver,
        },
        props: {
            storage: {},
        },
        data() {
            return {
                settingsMail: {
                    mail_address: '',
                    mail_pass: '',
                    mail_host: '',
                    mail_port: '465',
                    encryption: 'true',
                },
                status: 'process',
            };
        },
        created() {
            this.settingsMail = {
                ...this.settingsMail,
                ...this.storage,
            };

            this.$emit('setStatus', this.status);
        },
        watch: {
            settingsMail: {
                handler() {
                    if ('validate' in this.$refs) {
                        this.$refs.validate.validate().then(validated => {
                            if (validated) {
                                this.status = 'finish';
                                this.$emit('updateStorage', this.settingsMail);
                            } else {
                                this.status = 'process';
                            }
                            this.$emit('setStatus', this.status);
                        });
                    }
                },
                deep: true,
            },
            storage(val) {
                this.settingsMail = val;
            },
        },
    };
</script>
<style lang="scss" scoped>
    .row-inputs {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        justify-content: space-between;
    }
    .encryption {
        display: flex;
        flex-direction: column;
        margin-left: 30px;
    }
    .port {
        display: flex;
        width: 75px;
        flex-direction: column;
    }
</style>
