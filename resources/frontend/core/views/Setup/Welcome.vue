<template>
    <div>
        <validation-observer ref="validateObs">
            <validation-provider v-slot="{ errors }" name="Language" rules="required">
                <small>{{ $t('setup.header.welcome.language') }}</small>
                <at-select v-model="data.language" :placeholder="$t('control.select')" @on-change="onLanguageChange">
                    <at-option v-for="(language, index) in languageList" :key="index" :value="index">
                        {{ language }}
                    </at-option>
                </at-select>
                <p>{{ errors[0] }}</p>
            </validation-provider>
        </validation-observer>
    </div>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import { getLangCookie, setLangCookie } from '@/i18n';

    export default {
        name: 'Welcome',
        components: {
            ValidationProvider,
            ValidationObserver,
        },
        props: {
            storage: Object,
        },
        data() {
            return {
                data: {
                    language: '',
                },
            };
        },
        created() {
            this.data = { ...this.data, ...this.storage };

            if (!this.data.language) {
                this.$set(this.data, 'language', getLangCookie());
                this.$emit('updateStorage', this.data);
            }

            this.$emit('setStatus', 'finish');
        },
        computed: {
            languageList() {
                return this.$store.getters['lang/langList'];
            },
        },
        methods: {
            onLanguageChange(val) {
                setLangCookie(val);
                this.$i18n.locale = val;

                this.$emit('updateStorage', this.data);
            },
        },
        watch: {
            storage(val) {
                this.data = val;
            },
        },
    };
</script>
