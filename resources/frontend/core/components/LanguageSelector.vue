<template>
    <at-select v-if="Object.keys(languages).length > 0" :value="value" @on-change="inputHandler($event)">
        <at-option v-for="(lang, index) in languages" :key="index" :value="lang.value">
            {{ lang.label }}
        </at-option>
    </at-select>
</template>

<script>
    import { mapGetters } from 'vuex';

    export default {
        props: {
            value: {
                type: [Number, String],
                required: true,
            },
        },
        computed: {
            ...mapGetters('lang', ['langList']),

            languages() {
                return Object.keys(this.langList).map(p => ({
                    value: p,
                    label: this.langList[p],
                }));
            },
        },
        methods: {
            inputHandler(ev) {
                this.$emit('setLanguage', ev);
            },
        },
    };
</script>
