<template>
    <at-radio-group ref="select" v-model="val" class="enabled-screenshots-select">
        <at-radio-button
            v-for="option in options"
            :key="option"
            :label="option"
            :disabled="propertyInheritance"
            class="enabled-screenshots-select__btn"
        >
            <div>
                <slot :name="`role__name`">
                    {{ $t(`control.screenshot_select_options.${option}`) }}
                </slot>
            </div>
        </at-radio-button>
    </at-radio-group>
</template>

<script>
    export default {
        props: {
            value: {
                type: String,
                default: () => 'forbidden',
            },
            propertyInheritance: {
                type: Boolean,
                default: () => false,
                required: false,
            },
        },
        data() {
            return {
                options: ['required', 'optional', 'forbidden'],
            };
        },
        methods: {
            inputHandler(value) {
                this.$emit('input', value);
                this.$emit('updateProps', value);
            },
        },
        computed: {
            val: {
                get() {
                    return this.value;
                },
                set(value) {
                    this.inputHandler(value);
                },
            },
        },
    };
</script>

<style lang="scss" scoped>
    .role-select {
        &__description {
            white-space: normal;
            opacity: 0.6;
            font-size: 0.7rem;
        }
    }
    .enabled-screenshots-select {
        &::v-deep {
            .at-radio--checked {
                .at-radio-button__inner {
                    background-color: #6c6cff;
                    border-color: #6c6cff;
                }
            }
        }
    }
</style>
