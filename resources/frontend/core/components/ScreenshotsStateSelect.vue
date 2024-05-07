<template>
    <div>
        <at-radio-group ref="select" v-model="model" class="screenshots-state-select">
            <at-radio-button
                v-for="(state, key) in states"
                :key="key"
                :label="key"
                :disabled="isDisabled"
                class="screenshots-state-select__btn"
            >
                <div>
                    <slot :name="`state__name`">
                        {{ $t(`control.screenshot_state_options.${state}`) }}
                    </slot>
                </div>
            </at-radio-button>
        </at-radio-group>
        <div v-if="hint.length > 0" class="hint">{{ $t(hint) }}</div>
    </div>
</template>

<script>
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
            hint: {
                type: String,
                required: false,
                default: () => '',
            },
        },
        methods: {
            inputHandler(value) {
                this.$emit('input', value);
                this.$emit('updateProps', value);
            },
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

                Object.keys(this.$store.getters['screenshots/states']).forEach((item, i) => {
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

    .hint {
        font-size: 12px;
    }
</style>
