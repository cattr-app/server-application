<template>
    <div class="at-select-wrapper">
        <at-select
            ref="select"
            v-model="model"
            multiple
            filterable
            placeholder=""
            :size="size"
            @click="onClick"
            @input="onChange"
        >
            <li v-if="showSelectAll" class="at-select__option" @click="selectAll()">
                {{ $t('control.select_all') }}
            </li>
            <slot name="before-options"></slot>
            <at-option
                v-for="option of options"
                :key="option.id"
                :value="option.id"
                :label="option.name"
                @on-select-close="onClose"
            >
            </at-option>
        </at-select>
        <span v-if="showCount" class="at-select__placeholder">
            {{ placeholderText }}
        </span>
        <i v-if="model.length > 0" class="icon icon-x at-select__clear" @click.stop="clearSelect"></i>
    </div>
</template>

<script>
    import ResourceService from '../services/resource.service';

    export default {
        props: {
            service: {
                type: ResourceService,
            },
            selected: {
                type: [String, Number, Array, Object],
                default: Array,
            },
            inputHandler: {
                type: Function,
            },
            prependName: {
                type: String,
                default: '',
            },
            showSelectAll: {
                type: Boolean,
                default: true,
            },
            placeholder: {
                type: String,
                required: true,
            },
            size: {
                type: String,
                default: 'normal',
            },
        },
        data() {
            return {
                model: [],
                showCount: true,
                options: [],
            };
        },
        async created() {
            try {
                const all = await this.service.getAll({ headers: { 'X-Paginate': 'false' } });
                this.options.push(...all);
                this.$emit('onOptionsLoad', this.options);
            } catch ({ response }) {
                if (process.env.NODE_ENV === 'development') {
                    console.warn(response ? response : 'request to projects is canceled');
                }
            }

            if (Array.isArray(this.selected)) {
                this.model = this.selected;
            }

            this.$nextTick(() => {
                this.model.forEach(modelValue => {
                    if (this.$refs.select && Object.prototype.hasOwnProperty.call(this.$refs.select, '$children')) {
                        this.$refs.select.$children.forEach(option => {
                            if (option.value === modelValue) {
                                option.selected = true;
                            }
                        });
                    }
                });
            });

            this.lastQuery = '';
            this.$watch(
                () => {
                    if (this.$refs.select === undefined) {
                        return;
                    }

                    return {
                        query: this.$refs.select.query,
                        visible: this.$refs.select.visible,
                    };
                },
                ({ query, visible }) => {
                    if (visible) {
                        if (query.length) {
                            this.lastQuery = query;
                        } else {
                            if (
                                ['input', 'keypress'].includes(window?.event?.type) ||
                                window?.event?.key === 'Backspace'
                            ) {
                                // If query changed by user typing, save query
                                this.lastQuery = query;
                            } else {
                                // If query changed by clicking option and so on, restore query
                                this.$refs.select.query = this.lastQuery;
                            }
                        }
                    } else {
                        this.lastQuery = query;
                    }
                },
            );
        },
        watch: {
            model(value) {
                if (this.inputHandler) {
                    this.inputHandler(value);
                }
            },
        },
        methods: {
            selectAll(predicate = () => true) {
                console.log(this.$refs.select);
                const query = this.$refs.select.query.toUpperCase();
                this.model = this.options
                    .filter(({ name }) => name.toUpperCase().indexOf(query) !== -1)
                    .filter(predicate)
                    .map(({ id }) => id);
            },
            clearSelect() {
                this.$emit('input', []);
                this.model = [];
            },
            onClick() {
                if (this.showCount) {
                    this.showCount = false;
                } else {
                    setTimeout(() => {
                        this.showCount = true;
                    }, 300);
                }
            },
            onClose() {
                this.$refs.select.query = '';

                if (!this.showCount) {
                    setTimeout(() => {
                        this.showCount = true;
                    }, 300);
                }
            },
            onChange(val) {
                if (this.inputHandler) {
                    this.inputHandler(val);
                }
            },
        },
        computed: {
            selectionAmount() {
                return this.model.length;
            },
            allOptionsSelected() {
                return this.options.length === this.selectionAmount;
            },
            placeholderText() {
                const i18nKey = this.placeholder + (this.allOptionsSelected ? '_all' : '');
                return this.$tc(i18nKey, this.selectionAmount, {
                    count: this.selectionAmount,
                });
            },
        },
    };
</script>

<style lang="scss" scoped>
    .at-select-wrapper {
        position: relative;
    }

    .at-select {
        min-width: 240px;

        &__placeholder {
            left: 0;
            position: absolute;
            z-index: 1;
            font-size: 0.9rem;
        }

        &--small ~ &__placeholder {
            font-size: 11px;
            padding: 5px 24px 0 8px;
        }

        &__clear {
            margin-right: $spacing-05;
            display: block;
            cursor: pointer;
        }
    }

    ::v-deep {
        .at-select {
            &__placeholder {
                color: #3f536d;
                padding: 10px 12px;
            }

            &__input {
                height: 100%;
                z-index: 2;
            }

            &__selection {
                border-radius: 5px;
                color: black;
            }

            &--visible + .at-select__placeholder {
                display: none;
            }

            &__clear {
                z-index: 3;
            }

            &__arrow {
                z-index: 3;
            }
        }

        .at-tag {
            display: none;
        }
    }
</style>
