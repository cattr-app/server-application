<template>
    <div class="priority-select" :style="{ background: color, color: getTextColor(color) }">
        <at-select
            v-if="options.length"
            ref="select"
            v-model="model"
            :placeholder="$t('control.select')"
            filterable
            clearable="clearable"
        >
            <at-option
                v-for="option of options"
                :key="option.value"
                :label="ucfirst(option.label)"
                :value="option.value"
            >
                <span class="option" :style="{ background: option.color, color: getTextColor(option.color) }">
                    <span class="option-text">
                        {{ ucfirst(option.label) }}
                    </span>
                </span>
            </at-option>
        </at-select>
        <at-input v-else disabled></at-input>
    </div>
</template>

<script>
    import { getTextColor } from '@/utils/color';
    import { ucfirst } from '@/utils/string';
    import PriorityService from '@/services/resource/priority.service';

    export default {
        name: 'PrioritySelect',
        props: {
            value: {
                type: [String, Number],
                default: '',
            },
            clearable: {
                type: Boolean,
                default: () => false,
            },
        },
        async created() {
            try {
                this.options = await this.service.getAll().then(data => {
                    return data.map(option => {
                        return {
                            value: option.id,
                            label: option['name'],
                            color: option.color,
                        };
                    });
                });

                await this.$nextTick();

                if (this.$refs.select && Object.prototype.hasOwnProperty.call(this.$refs.select, '$children')) {
                    this.$refs.select.$children.forEach(option => {
                        option.hidden = false;
                    });
                }
            } catch ({ response }) {
                if (process.env.NODE_ENV === 'development') {
                    console.warn(response ? response : 'request to resource is canceled');
                }
            }
        },
        data() {
            return {
                options: [],
                service: new PriorityService(),
            };
        },
        methods: {
            ucfirst,
            getTextColor,
        },
        computed: {
            model: {
                get() {
                    return this.value;
                },
                set(value) {
                    this.$emit('input', value);
                },
            },
            color() {
                const option = this.options.find(option => +option.value === +this.value);
                if (typeof option === 'undefined') {
                    return 'transparent';
                }

                return option.color;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .priority-select {
        border-radius: 5px;

        &::v-deep .at-select__selection {
            background: transparent;
        }

        &::v-deep .at-select__dropdown .at-select__option {
            padding: 0;
        }

        &::v-deep .at-select {
            color: inherit;
        }
    }

    .option {
        display: block;
        width: 100%;
        padding: 6px 12px;
    }
</style>
