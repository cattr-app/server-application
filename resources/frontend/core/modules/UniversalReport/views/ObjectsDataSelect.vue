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
                        :key="option.id"
                        :class="{
                            'select-item': true,
                            active: selectedOptions.includes(option.id),
                        }"
                        @click="toggle(option.id)"
                    >
                        <UserAvatar
                            v-if="base === 'user'"
                            class="user-avatar"
                            :size="25"
                            :borderRadius="5"
                            :user="option"
                            :online="option.online"
                        />

                        <div class="name">
                            {{ typeof option.name !== 'undefined' ? option.name : option.full_name }}
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    import UserAvatar from '@/components/UserAvatar';
    import Preloader from '@/components/Preloader';

    export default {
        name: 'ObjDataSelect',
        components: {
            UserAvatar,
            Preloader,
        },
        props: {
            base: {
                type: String,
                default: () => '',
                required: false,
            },
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
                                    let name = typeof option.name !== 'undefined' ? option.name : option.full_name;

                                    return name.toUpperCase().indexOf(this.searchValue.toUpperCase()) !== -1;
                                })
                                .map(({ id }) => id)
                                .filter(id => !this.selectedOptions.includes(id)),
                        ),
                    );
                } else {
                    this.$emit(
                        'on-change',
                        this.selectedOptions.filter(pid => !this.options.map(({ id }) => id).includes(pid)),
                    );
                }
            },
            toggle(id) {
                if (this.selectedOptions.includes(id)) {
                    this.$emit(
                        'on-change',
                        this.selectedOptions.filter(i => i !== id),
                    );
                } else {
                    let selectedOptions = this.selectedOptions.slice(0);
                    selectedOptions.push(id);
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
                    let name =
                        typeof option.name !== 'undefined' ? option.name.toUpperCase() : option.full_name.toUpperCase();

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
