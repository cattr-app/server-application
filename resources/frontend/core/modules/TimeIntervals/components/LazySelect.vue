<template>
    <div class="at-select">
        <v-select
            :key="userID"
            :options="options"
            :filterable="false"
            :loading="isLoading"
            label="label"
            :placeholder="$t('time_intervals.task_select.placeholder')"
            :clearable="false"
            @input="inputHandler($event.id)"
            @open="onOpen"
            @close="onClose"
            @search="onSearch"
        >
            <div slot="no-options">
                <template v-if="loadFailed">
                    {{ $t('time_intervals.task_select.load_failed') }}
                    <button type="button" class="task-select__retry" @click.stop="retryLoad">
                        {{ $t('time_intervals.task_select.retry') }}
                    </button>
                </template>
                <template v-else-if="isLoading || (query.length >= 3 && currentPage === 0)">
                    {{ $t('time_intervals.task_select.loading') }}
                </template>
                <template v-else>{{ $t('time_intervals.task_select.no_options') }}</template>
            </div>
            <template #list-footer>
                <li v-if="options.length && (hasNextPage || loadFailed)" ref="load" class="task-select__loader">
                    <template v-if="loadFailed">
                        {{ $t('time_intervals.task_select.load_failed') }}
                        <button type="button" class="task-select__retry" @click.stop="retryLoad">
                            {{ $t('time_intervals.task_select.retry') }}
                        </button>
                    </template>
                    <template v-else>
                        {{ $t('time_intervals.task_select.loading') }}
                        <i class="icon icon-loader" />
                    </template>
                </li>
            </template>
        </v-select>
        <i class="icon icon-chevron-down at-select__arrow" />
    </div>
</template>

<script>
    import vSelect from 'vue-select';
    import debounce from 'lodash/debounce';

    export default {
        name: 'LazySelect',
        components: {
            vSelect,
        },
        props: {
            value: {
                type: Number,
            },
            userID: {
                type: Number,
            },
            service: {
                type: Object,
                required: true,
            },
            inputHandler: {
                type: Function,
                required: true,
            },
        },
        data() {
            return {
                options: [],
                query: '',
                currentPage: 0,
                totalPages: 0,
                isLoading: false,
                loadFailed: false,
                isOpen: false,
                observer: null,
                requestId: 0,
            };
        },
        computed: {
            hasNextPage() {
                return !this.loadFailed && this.currentPage < this.totalPages;
            },
        },
        watch: {
            userID() {
                this.inputHandler(null);
                this.restartSearch();
            },
        },
        created() {
            this.debouncedSearch = debounce(this.loadNextPage, 350);
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);
        },
        beforeDestroy() {
            this.debouncedSearch.cancel();
            this.requestId += 1;
            this.observer?.disconnect();
            this.observer = null;
        },
        methods: {
            onOpen() {
                this.isOpen = true;
                this.$nextTick(this.observeNextPage);
            },
            onClose() {
                this.isOpen = false;
                this.observer?.disconnect();
            },
            onSearch(query) {
                this.query = query.trim();
                this.restartSearch();
            },
            restartSearch() {
                this.debouncedSearch.cancel();
                this.observer?.disconnect();
                this.requestId += 1;
                this.options = [];
                this.currentPage = 0;
                this.totalPages = 0;
                this.isLoading = false;
                this.loadFailed = false;

                if (this.query.length >= 3) {
                    this.debouncedSearch(this.requestId);
                }
            },
            retryLoad() {
                if (this.loadFailed && !this.isLoading) {
                    this.loadFailed = false;
                    this.loadNextPage(this.requestId);
                }
            },
            infiniteScroll([{ isIntersecting }]) {
                if (isIntersecting && this.observer) {
                    this.observer.disconnect();
                    this.loadNextPage(this.requestId);
                }
            },
            observeNextPage() {
                if (!this.observer) {
                    return;
                }

                this.observer.disconnect();
                if (this.isOpen && this.hasNextPage && this.$refs.load) {
                    this.observer.observe(this.$refs.load);
                }
            },
            async loadNextPage(requestId) {
                if (
                    requestId !== this.requestId ||
                    this.query.length < 3 ||
                    this.isLoading ||
                    this.loadFailed ||
                    (this.currentPage > 0 && !this.hasNextPage)
                ) {
                    return;
                }

                const filters = {
                    search: { query: this.query, fields: ['task_name'] },
                    with: ['project'],
                    page: this.currentPage + 1,
                };
                if (this.userID) {
                    filters.where = { 'users.id': this.userID };
                }

                this.isLoading = true;
                let scrollContainer = null;
                let scrollTop = 0;
                try {
                    const { data } = await this.service.getWithFilters(filters);
                    if (requestId !== this.requestId) {
                        return;
                    }

                    // Vue Select can scroll back to the first option when results change.
                    scrollContainer = this.$refs.load?.offsetParent;
                    scrollTop = scrollContainer?.scrollTop ?? 0;

                    this.currentPage = data.pagination.currentPage;
                    this.totalPages = data.pagination.totalPages;
                    this.options.push(
                        ...data.data.map(task => ({
                            ...task,
                            label: task.project ? `${task.task_name} (${task.project.name})` : task.task_name,
                        })),
                    );
                } catch (error) {
                    if (requestId === this.requestId) {
                        this.loadFailed = true;
                        console.error('Failed to load tasks for time interval', error);
                    }
                } finally {
                    if (requestId === this.requestId) {
                        this.isLoading = false;
                        await this.$nextTick();
                        if (this.isOpen && scrollContainer?.isConnected) {
                            scrollContainer.scrollTop = scrollTop;
                        }
                        this.observeNextPage();
                    }
                }
            },
        },
    };
</script>

<style scoped>
    .task-select__loader {
        padding: 8px;
        text-align: center;
    }

    .task-select__retry {
        margin-left: 6px;
        padding: 0;
        border: 0;
        background: none;
        color: inherit;
        cursor: pointer;
        text-decoration: underline;
    }
</style>
