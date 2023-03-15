<template>
    <validation-observer ref="validateObs">
        <validation-provider v-slot="{ errors }" rules="required" name="Timezone">
            <small>{{ $t('setup.header.company_settings.timezone') }}</small>
            <timezone-picker :value="companySettings.timezone" @onTimezoneChange="onTimezoneChange" />
            <p>{{ errors[0] }}</p>
        </validation-provider>
    </validation-observer>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import TimezonePicker from '@/components/TimezonePicker.vue';

    export default {
        name: 'CompanySettings',
        components: {
            ValidationProvider,
            ValidationObserver,
            TimezonePicker,
        },
        props: {
            storage: {},
        },
        data() {
            return {
                companySettings: {
                    timezone: '',
                },
            };
        },
        mounted() {
            this.companySettings = {
                ...this.companySettings,
                ...this.storage,
            };

            this.$emit('setStatus', 'process');
        },
        methods: {
            onTimezoneChange(val) {
                this.companySettings.timezone = val;

                this.$emit('updateStorage', this.companySettings);
                this.$emit('setStatus', 'finish');
            },
        },
        watch: {
            storage(val) {
                this.companySettings = val;
            },
        },
    };
</script>
