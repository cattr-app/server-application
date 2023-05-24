<template>
    <at-radio-group ref="select" v-model="model" class="screenshots-state-select">
        <at-radio-button
            v-for="state in states"
            :key="state.value"
            :label="state.value"
            :disabled="isDisabled"
            class="screenshots-state-select__btn"
        >
            <div>
                <slot :name="`state__name`">
                    {{ $t(`control.screenshot_state_options.${state.name}`) }}
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
            hideIndexes: {
                type: Array,
                required: false,
                default: () => [],
            },
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
                let states = [];
                this.$store.getters['screenshots/states'].forEach((item, i) => {
                    if (!this.hideIndexes.includes(i)) {
                        return states.push(item);
                    }
                });
                return states;
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
