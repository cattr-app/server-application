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
                    <li
                        v-for="option in filteredOptions"
                        :key="option"
                        :class="{
                            'select-item': true,
                            active: selectedOptions.includes(option),
                        }"
                        @click="toggle(option)"
                    >
                        <div class="name">
                            {{ $t(`${localePath}.${option}`) }}
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    import Preloader from '@/components/Preloader';

    export default {
        name: 'VSelect',
        components: {
            Preloader,
        },
        props: {
            options: {
                type: Array,
                default: () => [],
                required: true,
            },
            selectedOptions: {
                type: Array,
                default: () => [],
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
                // If some users already selected we are going to clear it
                if (!this.selectedOptions.length) {
                    this.$emit(
                        'on-change',
                        this.selectedOptions.concat(
                            this.options
                                .filter(option => {
                                    let name = option;

                                    return name.toUpperCase().indexOf(this.searchValue.toUpperCase()) !== -1;
                                })
                                .filter(option => !this.selectedOptions.includes(option)),
                        ),
                    );
                } else {
                    this.$emit(
                        'on-change',
                        this.selectedOptions.filter(option => !this.options.map(item => item).includes(option)),
                    );
                }
            },
            toggle(option) {
                if (this.selectedOptions.includes(option)) {
                    this.$emit(
                        'on-change',
                        this.selectedOptions.filter(item => item !== option),
                    );
                } else {
                    let selectedOptions = this.selectedOptions.slice(0);
                    selectedOptions.push(option);
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
            inputValue() {
                return this.$tc('control.element_selected', this.selectedOptions.length, {
                    count: this.selectedOptions.length,
                });
            },
            filteredOptions() {
                return this.options.filter(option => {
                    let name = option.toUpperCase();

                    const value = this.searchValue.toUpperCase();

                    return name.indexOf(value) !== -1;
                });
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
</style>
