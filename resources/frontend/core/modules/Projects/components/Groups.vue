<template>
    <div class="group-select">
        <v-select
            v-model="model"
            :options="options"
            :filterable="false"
            label="label"
            :clearable="true"
            :reduce="option => option.id"
            :components="{ Deselect, OpenIndicator }"
            @open="onOpen"
            @close="onClose"
            @search="onSearch"
            @option:selecting="handleSelecting"
        >
            <template #option="{ label, depth, current }">
                <span class="option" :class="{ 'option--current': current }">
                    <span class="option__text">
                        <span v-if="depth > 0" class="option__depth">{{ getSpaceByDepth(depth) }}</span>
                        <span>{{ ucfirst(label) }}</span>
                    </span>
                </span>
            </template>

            <template #no-options="{ search, searching }">
                <template v-if="searching">
                    <span>{{ $t('field.no_groups_found', { query: search }) }}</span>
                </template>
                <em v-else>{{ $t('field.no_groups_found', { query: search }) }}</em>
            </template>

            <template #list-footer>
                <li v-show="hasNextPage" ref="load" class="option__infinite-loader">
                    {{ $t('field.loading_groups') }} <i class="icon icon-loader" />
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

    export default {
        name: 'Groups',
        props: {
            value: {
                type: [String, Number],
                default: '',
            },
            currentGroup: {
                type: [Object, String],
                default: '',
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
                options: [],
                service: new ProjectGroupsService(),
                observer: null,
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
            this.requestTimestamp = Date.now();
            this.search(this.requestTimestamp);
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
                    this.$emit('setCurrent', {
                        id: this.localCurrentGroup.id,
                        name: this.localCurrentGroup.label,
                    });
                }
                if (newValue.value === '' && newValue.query === '') {
                    this.requestTimestamp = Date.now();
                    this.search(this.requestTimestamp);
                }
            },
        },
        methods: {
            ucfirst,
            getSpaceByDepth: function (depth) {
                return ''.padStart(depth, '-');
            },
            async onOpen() {
                this.isSelectOpen = true;
                await this.$nextTick();
                this.observe(this.requestTimestamp);
            },
            onClose() {
                this.isSelectOpen = false;
                this.observer.disconnect();
            },
            onSearch(query) {
                this.query = query;
                this.search.cancel();
                this.requestTimestamp = Date.now();

                if (this.query.length) {
                    this.search(this.requestTimestamp);
                }
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

                return this.service.getWithFilters(filters).then(({ data, pagination }) => {
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
                    this.options = [
                        {
                            id: this.currentGroup.id,
                            label: this.currentGroup.name,
                            depth: 0,
                            current: true,
                        },
                    ];
                    this.localCurrentGroup = this.options[0];
                } else {
                    this.options = [];
                }
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
        }
    }
</style>
