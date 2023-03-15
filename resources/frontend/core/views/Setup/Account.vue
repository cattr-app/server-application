<template>
    <validation-observer ref="validate" class="account">
        <validation-provider v-slot="{ errors }" rules="required|email" :name="$t('setup.header.account.email')">
            <h6 v-t="'setup.header.account.email'" />
            <at-input
                v-model="accountParams.email"
                name="Email"
                :status="errors.length > 0 ? 'error' : ''"
                :placeholder="$t('setup.header.account.email')"
                icon=""
                type="text"
            />
            <p v-html="errors[0]" />
        </validation-provider>

        <validation-provider v-slot="{ errors }" rules="required" :name="$t('setup.header.account.password')">
            <h6 v-t="'setup.header.account.password'" />
            <at-input
                v-model="accountParams.password"
                name="Password"
                :status="errors.length > 0 ? 'error' : ''"
                :placeholder="$t('setup.header.account.password')"
                icon=""
                type="password"
            />
            <p v-html="errors[0]" />
        </validation-provider>
    </validation-observer>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';

    export default {
        name: 'Account',
        components: {
            ValidationProvider,
            ValidationObserver,
        },
        props: {
            storage: {},
        },
        data() {
            return {
                accountParams: {
                    email: '',
                    password: '',
                },
                status: 'process',
            };
        },
        created() {
            this.accountParams = {
                ...this.accountParams,
                ...this.storage,
            };

            this.$emit('setStatus', this.status);
        },
        watch: {
            accountParams: {
                handler() {
                    if ('validate' in this.$refs) {
                        this.$refs.validate.validate().then(validated => {
                            if (validated) {
                                this.status = 'finish';

                                this.$emit('updateStorage', this.accountParams);
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
                this.accountParams = val;
            },
        },
    };
</script>
<style lang="scss" scoped>
    .account {
        width: 50%;
    }
</style>
