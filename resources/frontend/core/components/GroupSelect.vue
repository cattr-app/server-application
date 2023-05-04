<template>
    <div ref="groupSelect" class="group-select" @click="onActive">
        <div v-if="isActive != true">{{ model == '' ? $t('field.no_group_selected') : model }}</div>
        <v-select
            v-else
            v-model="model"
            :options="options"
            :filterable="false"
            label="label"
            :clearable="true"
            :reduce="option => option.label"
            :components="{ Deselect, OpenIndicator }"
            @open="onOpen"
            @close="onClose"
            @search="onSearch"
            @option:selecting="handleSelecting"
        >
            <template #option="{ id, label, depth, current }">
                <span class="option" :class="{ 'option--current': current }">
                    <span class="option__text">
                        <span v-if="depth > 0" class="option__depth">{{ getSpaceByDepth(depth) }}</span>
                        <span>{{ ucfirst(label) }}</span>
                        <span @click.stop>
                            <router-link
                                class="option__text__link"
                                :to="{ name: 'ProjectGroups.crud.groups.edit', params: { id: id } }"
                                target="_blank"
                            >
                                <i class="icon icon-external-link" />
                            </router-link>
                        </span>
                    </span>
                </span>
            </template>

            <template #no-options="{ search, searching }">
                <template v-if="searching">
                    <span>{{ $t('field.no_groups_found', { query: search }) }}</span>
                </template>
                <em v-else>{{ $t('field.no_groups_found', { query: search }) }}</em>
                <template>
                    <div class="no-option icon icon-plus-circle" @click="createGroup">
                        <span class="no-option__text">
                            <span>{{ $t('field.fast_create_group', { query: search }) }}</span>
                        </span>
                    </div>
                </template>
                <template>
                    <div class="no-option icon icon-plus-circle" @click="toCreateGroup">
                        <span class="no-option__text">
                            <span>{{ $t('field.to_create_group', { query: search }) }}</span>
                        </span>
                    </div>
                </template>
            </template>

            <template #list-footer>
                <li v-show="hasNextPage" ref="load" class="option__infinite-loader">
                    {{ $t('field.loading_groups') }} <i class="icon icon-loader"></i>
                </li>
            </template>
        </v-select>
    </div>
</template>

<script>
    import { ucfirst } from '@/utils/string';
    import ProjectGroupsService from '@/services/resource/project-groups.service';
    import vSelect from 'vue-select';
    import debounce from 'lodash/debounce';

    const service = new ProjectGroupsService();

    export default {
        name: 'GroupSelect',
        props: {
            value: {
                type: [String, Object],
                default: '',
            },
            currentGroup: {
                type: [Object],
                required: true,
            },
            clearable: {
                type: Boolean,
                default: () => false,
            },
        },
        components: {
            vSelect,
        },
        data() {
            return {
                isActive: false,
                options: [],
                isSelectOpen: false,
                totalPages: 0,
                currentPage: 0,
                query: '',
                lastSearchQuery: '',
                requestTimestamp: null,
                localCurrentGroup: null,
            };
        },
        created() {
            this.search = debounce(this.search, 350);
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);
        },
        watch: {
            async valueAndQuery(newValue) {
                if (newValue.value === '') {
                    this.localCurrentGroup != null
                        ? (this.localCurrentGroup.current = false)
                        : (this.localCurrentGroup = null);
                    this.$emit('setCurrent', '');
                } else {
                    if (newValue.value !== this.currentGroup.name) {
                        this.$emit('setCurrent', {
                            id: this.localCurrentGroup.id,
                            name: this.localCurrentGroup.label,
                        });
                    }
                }
                if (newValue.value === '' && newValue.query === '') {
                    this.requestTimestamp = Date.now();
                    this.search(this.requestTimestamp);
                }
            },
        },
        methods: {
            toCreateGroup() {
                this.$router.push({ name: 'ProjectGroups.crud.groups.new' });
            },
            createGroup() {
                service.save({ name: this.query }, true).then(({ data }) => {
                    this.$emit('createGroup', {
                        id: data.data.id,
                        name: data.data.name,
                    });
                });
                this.query = '';
                this.onClose();
            },
            ucfirst,
            getSpaceByDepth: function (depth) {
                return ''.padStart(depth, '-');
            },
            onActive() {
                this.isActive = true;
                this.$refs.groupSelect.parentElement.style.zIndex = 1;
                this.onSearch(this.query);
                this.onOpen();
            },
            async onOpen() {
                this.isSelectOpen = true;
                await this.$nextTick();
                this.observe(this.requestTimestamp);
            },
            onClose() {
                if (this.model == null) {
                    this.model = typeof this.currentGroup == 'object' ? this.currentGroup.name : this.currentGroup;
                }

                this.isActive = false;
                this.$refs.groupSelect.parentElement.style.zIndex = 0;
                this.isSelectOpen = false;
                this.observer.disconnect();
            },
            onSearch(query) {
                this.query = query;
                this.search.cancel();
                this.requestTimestamp = Date.now();

                this.search(this.requestTimestamp);
            },
            async search(requestTimestamp) {
                this.observer.disconnect();
                this.totalPages = 0;
                this.currentPage = 0;
                this.resetOptions();
                this.lastSearchQuery = this.query;
                await this.$nextTick();
                await this.loadOptions(requestTimestamp);
                await this.$nextTick();
                this.observe(requestTimestamp);
            },
            handleSelecting(option) {
                if (this.localCurrentGroup != null) {
                    this.localCurrentGroup.current = false;
                }
                option.current = true;
                this.localCurrentGroup = option;
            },
            async infiniteScroll([{ isIntersecting, target }]) {
                if (isIntersecting) {
                    const ul = target.offsetParent;
                    const scrollTop = target.offsetParent.scrollTop;
                    const requestTimestamp = +target.dataset.requestTimestamp;

                    if (requestTimestamp === this.requestTimestamp) {
                        await this.loadOptions(requestTimestamp);

                        await this.$nextTick();

                        ul.scrollTop = scrollTop;

                        this.observer.disconnect();
                        this.observe(requestTimestamp);
                    }
                }
            },
            observe(requestTimestamp) {
                if (this.isSelectOpen && this.$refs.load) {
                    this.$refs.load.dataset.requestTimestamp = requestTimestamp;
                    this.observer.observe(this.$refs.load);
                }
            },
            async loadOptions(requestTimestamp) {
                const filters = {
                    search: { query: this.lastSearchQuery, fields: ['name'] },
                    page: this.currentPage + 1,
                };

                return service.getWithFilters(filters).then(({ data, pagination }) => {
                    if (requestTimestamp === this.requestTimestamp) {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        data.forEach(option => {
                            option.current = false;
                            if (this.options[0]?.id === option.id) {
                                this.options.shift();
                                if (option.id === this.currentGroup?.id) {
                                    option.current = true;
                                }
                            }
                            this.options.push({
                                id: option.id,
                                label: option.name,
                                depth: option.depth,
                                current: option.current,
                            });

                            if (option.current) {
                                this.localCurrentGroup = this.options[this.options.length - 1];
                            }
                        });
                    }
                });
            },
            resetOptions() {
                if (typeof this.currentGroup === 'object' && this.currentGroup !== null) {
                    this.localCurrentGroup = {
                        id: this.currentGroup.id,
                        label: this.currentGroup.name,
                        depth: 0,
                        current: true,
                    };
                }
                this.options = [];
            },
        },
        computed: {
            model: {
                get() {
                    return this.value;
                },
                set(option) {
                    this.$emit('input', option);
                },
            },
            valueAndQuery() {
                return {
                    value: this.value,
                    query: this.query,
                };
            },
            Deselect() {
                return {
                    render: createElement =>
                        createElement('i', {
                            class: 'icon icon-x',
                        }),
                };
            },
            OpenIndicator() {
                return {
                    render: createElement =>
                        createElement('i', {
                            class: {
                                icon: true,
                                'icon-chevron-down': !this.isSelectOpen,
                                'icon-chevron-up': this.isSelectOpen,
                            },
                        }),
                };
            },
            hasNextPage() {
                return this.currentPage < this.totalPages;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .group-select {
        border-radius: 5px;

        &::v-deep {
            &:hover {
                .vs__clear {
                    display: inline-block;
                }
                .vs__open-indicator {
                    display: none;
                }
            }

            .vs--open {
                .vs__open-indicator {
                    display: inline-block;
                }
            }

            .vs__actions {
                display: flex;
                font-size: 14px;
                margin-right: 8px;
                width: 30px;
                position: relative;
            }

            .vs__clear {
                padding-right: 0;
                margin-right: 0;
                position: absolute;
                right: 0;
            }

            .vs__open-indicator {
                transform: none;
                position: absolute;
                right: 0;
            }

            .vs__no-options {
                padding: 0;
                font-family: inherit;
            }
        }
    }

    .option {
        &--current {
            font-weight: bold;
        }

        &__depth {
            padding-right: 0.3em;
            letter-spacing: 0.1em;
            opacity: 0.3;
            font-weight: 300;
        }

        &__infinite-loader {
            display: flex;
            justify-content: center;
            align-items: center;
            column-gap: 0.3rem;
            z-index: 2;
        }

        &__text__link {
            font-size: 15px;
        }
    }

    .no-option {
        margin-top: 10px;
        cursor: pointer;
        padding: 10px 0 10px 5px;
        border: 1px solid;
        opacity: 0.5;
        transition: all 1s;
        text-align: start;
        line-height: 20px;
        border-radius: 4px;
        &:hover {
            background: #000;
            color: white;
            opacity: 1;
            transition: all 1s;
        }
        &::before {
            margin-right: 5px;
        }
    }
</style>
