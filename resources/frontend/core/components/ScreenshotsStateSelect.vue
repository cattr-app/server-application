<template>
    <at-radio-group ref="select" v-model="model" class="screenshots-state-select">
        <at-radio-button
            v-for="(state, i) in states"
            v-show="i !== hideIndex"
            :key="state.value"
            :label="state.value"
            :disabled="isDisabled"
            class="screenshots-state-select__btn"
        >
            <div>
                <slot :name="`state__name`">
                    {{ $t(`control.screenshot_select_options.${state.name}`) }}
                </slot>
            </div>
        </at-radio-button>
    </at-radio-group>
</template>

<script>
    import { mapActions } from 'vuex';
    import ScreenshotService from '@/services/resource/screenshot.service';
    const service = new ScreenshotService();
    export default {
        props: {
            value: {
                type: Number,
                default: () => 1,
            },
            isDisabled: {
                type: Boolean,
                default: () => false,
                required: false,
            },
            hideIndex: {
                type: Number,
                required: false,
            },
        },
        mounted() {
            service.getStates().then(({ data }) => (this.options = data));
        },
        methods: {
            inputHandler(value) {
                this.$emit('input', value);
                this.$emit('updateProps', value);
            },
            ...mapActions({
                getStates: 'screenshots/loadStates',
            }),
        },
        created() {
            this.getStates();
        },
        computed: {
            model: {
                get() {
                    return this.value;
                },
                set(value) {
                    this.inputHandler(value);
                },
            },
            states() {
                return this.$store.getters['screenshots/states'];
            },
        },
    };
</script>

<style lang="scss" scoped>
    .screenshots-state-select {
        &::v-deep {
            .at-radio--checked {
                .at-radio-button__inner {
                    background-color: $blue-2;
                    border-color: $blue-2;
                }
            }
        }
    }
</style>
