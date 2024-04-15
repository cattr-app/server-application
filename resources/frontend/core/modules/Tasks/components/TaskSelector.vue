<template>
    <div class="task-select">
        <v-select
            v-model="model"
            :options="options"
            :filterable="false"
            label="label"
            :clearable="true"
            :reduce="option => option.id"
            :components="{ Deselect, OpenIndicator }"
            :placeholder="$t('tasks.relations.select_task')"
            @open="onOpen"
            @close="onClose"
            @search="onSearch"
            @option:selecting="handleSelecting"
        >
            <template #option="{ label, current }">
                <span class="option" :class="{ 'option--current': current }">
                    <span class="option__text">
                        <span>{{ ucfirst(label) }}</span>
                    </span>
                </span>
            </template>

            <template #no-options="{ search, searching }">
                <template v-if="searching">
                    <span>{{ $t('tasks.relations.no_task_found', { query: search }) }}</span>
                </template>
                <em v-else style="opacity: 0.5">{{ $t('tasks.relations.type_to_search') }}</em>
            </template>

            <template #list-footer>
                <li v-show="hasNextPage" ref="load" class="option__infinite-loader">
                    {{ $t('tasks.relations.loading') }} <i class="icon icon-loader" />
                </li>
            </template>
        </v-select>
    </div>
</template>

<script>
    // TODO: extract infinite scroll into separate component
    import { ucfirst } from '@/utils/string';
    import TasksService from '@/services/resource/task.service';
    import vSelect from 'vue-select';
    import debounce from 'lodash/debounce';

    const service = new TasksService();

    export default {
        name: 'TaskSelector',
        props: {
            value: {
                type: [String, Number],
                default: '',
            },
            projectId: {
                type: Number,
                default: null,
            },
        },
        components: {
            vSelect,
        },
        data() {
            return {
                options: [],
                observer: null,
                isSelectOpen: false,
                totalPages: 0,
                currentPage: 0,
                query: '',
                lastSearchQuery: '',
                requestTimestamp: null,
                localCurrentTask: null,
            };
        },
        created() {
            this.search = debounce(this.search, 350);
            this.requestTimestamp = Date.now();
            this.search(this.requestTimestamp);
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);
            if (typeof this.localCurrentTask === 'object' && this.localCurrentTask != null) {
                this.options = [
                    {
                        id: this.localCurrentTask.id,
                        label: this.localCurrentTask.task_name,
                        current: true,
                    },
                ];
                this.localCurrentTask = this.options[0];
            }
        },
        watch: {
            async valueAndQuery(newValue) {
                if (newValue.value === '') {
                    this.localCurrentTask != null
                        ? (this.localCurrentTask.current = false)
                        : (this.localCurrentTask = null);
                    this.localCurrentTask = null;
                    // this.$emit('setCurrent', '');
                } else {
                    // this.$emit('setCurrent', {
                    //     id: this.localCurrentTask.id,
                    //     name: this.localCurrentTask.label,
                    // });
                }
                if (newValue.value === '' && newValue.query === '') {
                    this.requestTimestamp = Date.now();
                    this.search(this.requestTimestamp);
                }
            },
        },
        methods: {
            ucfirst,
            async onOpen() {
                // this.requestTimestamp = Date.now();
                // this.search(this.requestTimestamp);
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
                if (this.localCurrentTask != null) {
                    this.localCurrentTask.current = false;
                }
                option.current = true;
                this.localCurrentTask = option;
                // this.$emit('setCurrent', {
                //     id: this.localCurrentTask.id,
                //     name: this.localCurrentTask.label,
                // });
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
                    search: { query: this.lastSearchQuery, fields: ['task_name'] },
                    page: this.currentPage + 1,
                };

                if (this.projectId) {
                    filters['where'] = { project_id: this.projectId };
                }
                // async fetchTasks(query, loading) {
                //     loading(true);
                //
                //     const filters = { search: { query, fields: ['task_name'] }, with: ['project'] };
                //     if (this.userID) {
                //         filters['where'] = { 'users.id': this.userID };
                //     }
                //
                //     this.options = await this.service.getWithFilters(filters).then(({ data }) => {
                //         loading(false);
                //
                //         return data.data.map(task => {
                //             const label =
                //                 typeof task.project !== 'undefined'
                //                     ? `${task.task_name} (${task.project.name})`
                //                     : task.task_name;
                //
                //             return { ...task, label };
                //         });
                //     });
                // },

                return service.getWithFilters(filters).then(res => {
                    const data = res.data.data;
                    const pagination = res.data.pagination;
                    if (requestTimestamp === this.requestTimestamp) {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        data.forEach(option => {
                            option.current = false;
                            if (this.options[0]?.id === option.id) {
                                this.options.shift();
                                if (option.id === this.localCurrentTask?.id) {
                                    option.current = true;
                                }
                            }
                            this.options.push({
                                id: option.id,
                                label: option.task_name,
                                current: option.current,
                            });

                            if (option.current) {
                                this.localCurrentTask = this.options[this.options.length - 1];
                            }
                        });
                    }
                });
            },
            resetOptions() {
                if (typeof this.localCurrentTask === 'object' && this.localCurrentTask !== null) {
                    this.options = [
                        {
                            id: this.localCurrentTask.id,
                            label: this.localCurrentTask.label,
                            current: true,
                        },
                    ];
                    this.localCurrentTask = this.options[0];
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
                    if (option == null) {
                        this.localCurrentTask = null;
                        this.requestTimestamp = Date.now();
                        this.search(this.requestTimestamp);
                    }
                    this.$emit('change', option);
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
    .task-select {
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

        &__infinite-loader {
            display: flex;
            justify-content: center;
            align-items: center;
            column-gap: 0.3rem;
        }
    }
</style>
