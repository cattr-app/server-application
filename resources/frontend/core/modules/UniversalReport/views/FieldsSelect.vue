<template>
    <div class="select" :class="{ 'at-select--visible': showPopup }" @click="togglePopup">
        <at-input class="select-input" :readonly="true" :value="inputValue" :size="size" />

        <span
            v-show="selectedOptions.length"
            class="select__clear icon icon-x at-select__clear"
            @click="clearSelection"
        />

        <span class="icon icon-chevron-down at-select__arrow" />
        <div v-show="showPopup" class="at-select__dropdown at-select__dropdown--bottom" @click.stop>
            <div class="search">
                <at-input v-model="searchValue" class="search-input" :placeholder="$t('control.search')" />
            </div>

            <div class="select-all" @click="selectAll">
                <span>{{ $t(selectedOptions.length ? 'control.clear_all' : 'control.select_all') }}</span>
            </div>

            <div class="select-list">
                <preloader v-if="isLoading"></preloader>
                <ul>
                    <div v-for="(o, key) in filteredOptions" :key="key">
                        <p>{{ $t(`universal-report.${key}`) }}</p>
                        <li
                            v-for="option in o"
                            :key="option"
                            :class="{
                                'select-item': true,
                                active: selectedOptions[key].includes(option),
                            }"
                            @click="toggle(option, key)"
                        >
                            <div class="name">
                                {{ $t(`${localePath}.${selectedBase}.${key}.${option}`) }}
                            </div>
                        </li>
                    </div>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    import Preloader from '@/components/Preloader';
    import cloneDeep from 'lodash/cloneDeep';
    import { mapGetters } from 'vuex';

    export default {
        name: 'FieldsSelect',
        components: {
            Preloader,
        },
        props: {
            options: {
                type: Object,
                default: () => {},
                required: true,
            },
            selectedOptions: {
                type: Object,
                default: () => {},
                required: false,
            },
            size: {
                type: String,
                default: 'normal',
            },
            localePath: {
                type: String,
                default: () => '',
                required: true,
            },
        },
        data() {
            return {
                showPopup: false,
                searchValue: '',
                isLoading: false,
            };
        },
        methods: {
            selectAll() {
                if (JSON.stringify(this.options) !== JSON.stringify(this.selectedOptions)) {
                    this.$emit('on-change', this.options);
                }
            },
            toggle(option, key) {
                let selectedOptions = cloneDeep(this.selectedOptions);

                if (selectedOptions[key].includes(option)) {
                    selectedOptions[key] = selectedOptions[key].filter(item => item !== option);
                    this.$emit('on-change', selectedOptions);
                } else {
                    selectedOptions[key].push(option);
                    this.$emit('on-change', selectedOptions);
                }
            },
            clearSelection() {
                this.$emit('on-change', []);
            },
            togglePopup() {
                this.showPopup = !this.showPopup;

                if (!this.showPopup) {
                    this.$emit('on-change', this.selectedOptions);
                }
            },
            hidePopup() {
                if (this.$el.contains(event.target)) {
                    return;
                }

                this.showPopup = false;
                this.$emit('on-change', this.selectedOptions);
            },
        },
        computed: {
            ...mapGetters('universalreport', ['selectedBase']),
            inputValue() {
                let count = 0;
                let selectedFields = {};
                Object.assign(selectedFields, this.selectedOptions);
                Object.keys(selectedFields).forEach(key => (count += selectedFields[key].length));
                return this.$tc('control.element_selected', count, {
                    count: count,
                });
            },
            filteredOptions() {
                let result = {};
                Object.keys(this.options).forEach(key => {
                    return (result[key] = this.options[key].filter(option => {
                        let name = option.toUpperCase();

                        const value = this.searchValue.toUpperCase();

                        return name.indexOf(value) !== -1;
                    }));
                });

                return result;
            },
        },
        created() {
            window.addEventListener('click', this.hidePopup);
        },
        beforeDestroy() {
            window.removeEventListener('click', this.hidePopup);
        },
    };
</script>

<style lang="scss" scoped>
    .select {
        position: relative;
        min-width: 240px;

        &::v-deep {
            .at-input__original {
                border-radius: 5px;

                padding-right: $spacing-08;
                cursor: text;
            }
        }

        &__clear {
            margin-right: $spacing-05;
            display: block;
        }

        &-list {
            overflow-y: scroll;
            max-height: 200px;
            position: relative;
            min-height: 60px;
        }

        &-all {
            position: relative;
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: #59566e;
            text-transform: uppercase;

            padding: 8px 20px;

            cursor: pointer;
        }

        &-item {
            font-size: 13px;
            font-weight: 500;
            color: #151941;
            cursor: pointer;

            display: flex;
            align-items: center;

            padding: 7px 20px;

            &.active {
                background: #f4f4ff;
            }

            &::before,
            &::after {
                content: ' ';
                display: table;
                clear: both;
            }
        }
    }

    .search-input {
        margin: 0;

        &::v-deep {
            .at-input__original {
                border: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }
    }

    .user-avatar {
        float: left;
        margin-right: 10px;
    }

    .name {
        padding-bottom: 3px;
    }

    .at-select {
        &__dropdown {
            overflow: hidden;
            max-height: 360px;
        }
    }
    p {
        margin-left: 16px;
    }
</style>
