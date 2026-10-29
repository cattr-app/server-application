<template>
    <div class="at-select">
        <v-select
            :key="userID"
            :options="options"
            :value="selectedOption"
            :filterable="false"
            label="label"
            :placeholder="$t('time_intervals.task_select.placeholder')"
            :clearable="false"
            @input="onSelect"
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
        <small v-if="selectionLoading">{{ $t('time_intervals.task_select.checking') }}</small>
        <small v-else-if="selectionError" role="alert">{{ $t(selectionError) }}</small>
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
            preselectedId: {
                type: [Number, String],
                default: null,
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
                selectedTask: null,
                selectionLoading: false,
                selectionError: null,
                selectionRequestId: 0,
                pendingTaskId: null,
            };
        },
        computed: {
            selectedOption() {
                if (!this.value) return null;
                const id = Number(this.value);
                if (this.selectedTask && Number(this.selectedTask.id) === id) return this.selectedTask;
                return this.options.find(task => Number(task.id) === id) || null;
            },
            hasNextPage() {
                return !this.loadFailed && this.currentPage < this.totalPages;
            },
        },
        watch: {
            userID(userID) {
                const oldTaskId = this.value || this.pendingTaskId;
                this.selectionRequestId += 1;
                this.pendingTaskId = null;
                this.selectedTask = null;
                this.selectionLoading = false;
                this.selectionError = null;
                this.inputHandler(null);
                this.restartSearch();
                if (oldTaskId && userID) this.hydrateTask(oldTaskId, userID, true);
            },
            preselectedId(taskId) {
                this.selectionRequestId += 1;
                this.pendingTaskId = null;
                this.selectedTask = null;
                this.selectionLoading = false;
                this.selectionError = null;
                this.inputHandler(null);
                if (taskId !== null && taskId !== undefined) this.hydrateTask(taskId, this.userID);
            },
        },
        created() {
            this.debouncedSearch = debounce(this.loadNextPage, 350);
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);
            if (this.preselectedId !== null && this.preselectedId !== undefined) {
                this.hydrateTask(this.preselectedId, this.userID);
            }
        },
        beforeDestroy() {
            this.debouncedSearch.cancel();
            this.requestId += 1;
            this.selectionRequestId += 1;
            this.observer?.disconnect();
            this.observer = null;
        },
        methods: {
            formatTask(task) {
                return {
                    ...task,
                    label: task.project ? task.task_name + ' (' + task.project.name + ')' : task.task_name,
                };
            },
            onSelect(task) {
                this.selectionRequestId += 1;
                this.pendingTaskId = null;
                this.selectionLoading = false;
                this.selectionError = null;
                this.selectedTask = task || null;
                this.inputHandler(task ? task.id : null);
            },
            async hydrateTask(rawId, userID, checkMembership = false) {
                const id = Number(rawId);
                if (!Number.isSafeInteger(id) || id <= 0 || String(rawId).trim() === '') {
                    this.inputHandler(null);
                    this.selectionError = 'time_intervals.task_select.invalid_id';
                    return;
                }
                this.pendingTaskId = id;
                if (!userID) return;
                const requestId = ++this.selectionRequestId;
                this.selectionLoading = true;
                this.selectionError = null;
                try {
                    const { data } = await this.service.getItem(id, { with: ['project'] });
                    const task = data && data.data;
                    if (!task || Number(task.id) !== id) throw new Error('task_not_available');
                    if (checkMembership) {
                        const { data: allowed } = await this.service.getWithFilters({
                            where: { id, 'users.id': userID },
                            page: 1,
                        });
                        if (!allowed || !Array.isArray(allowed.data) ||
                            !allowed.data.some(item => Number(item.id) === id)) {
                            throw new Error('task_not_available');
                        }
                    }
                    if (requestId !== this.selectionRequestId || userID !== this.userID) return;
                    this.selectedTask = this.formatTask(task);
                    this.inputHandler(id);
                } catch (error) {
                    if (requestId !== this.selectionRequestId || userID !== this.userID) return;
                    this.selectedTask = null;
                    this.inputHandler(null);
                    this.selectionError =
                        error.message === 'task_not_available' ||
                        (error.response && [403, 404].includes(error.response.status))
                            ? 'time_intervals.task_select.unavailable'
                            : 'time_intervals.task_select.load_failed';
                } finally {
                    if (requestId === this.selectionRequestId) {
                        this.pendingTaskId = null;
                        this.selectionLoading = false;
                    }
                }
            },
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
                        ...data.data.map(task => this.formatTask(task)),
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
