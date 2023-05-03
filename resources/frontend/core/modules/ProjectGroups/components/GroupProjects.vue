<template>
    <div class="projects">
        <h6 v-if="isDataLoading">{{ $t('message.loading_projects') }} <i class="icon icon-loader" /></h6>

        <at-input
            v-model="query"
            type="text"
            :placeholder="$t('message.project_search_input_placeholder')"
            class="projects__search col-6"
            @input="onSearch"
        >
            <template v-slot:prepend>
                <i class="icon icon-search" />
            </template>
        </at-input>

        <div class="at-container">
            <div ref="tableWrapper" class="table">
                <at-table ref="table" size="large" :columns="columns" :data="projects" />
            </div>
        </div>

        <at-pagination :total="projectsTotal" :current="page" :page-size="limit" @page-change="loadPage" />
    </div>
</template>

<script>
    import ProjectService from '@/services/resource/project.service';
    import TeamAvatars from '@/components/TeamAvatars';
    import i18n from '@/i18n';
    import debounce from 'lodash.debounce';

    const service = new ProjectService();

    export default {
        name: 'GroupProjects',
        props: {
            groupId: {
                type: Number,
                required: true,
            },
        },
        data() {
            return {
                projects: [],
                projectsTotal: 0,
                limit: 10,
                query: '',
                totalPages: 0,
                currentPage: 0,
                page: 1,
                isDataLoading: false,
            };
        },
        async created() {
            this.search = debounce(this.search, 350);
            this.requestTimestamp = Date.now();
            await this.search(this.requestTimestamp);
        },
        methods: {
            async loadPage(page) {
                this.page = page;
                await this.loadOptions();
            },
            onSearch() {
                this.requestTimestamp = Date.now();

                this.search(this.requestTimestamp);
            },
            async search(requestTimestamp) {
                this.totalPages = 0;
                this.resetOptions();
                await this.$nextTick();
                await this.loadOptions(requestTimestamp);
                await this.$nextTick();
            },
            async loadOptions(requestTimestamp) {
                const filters = {
                    where: {
                        group: ['in', [this.groupId]],
                    },
                    with: ['users', 'tasks', 'can'],
                    withCount: ['tasks'],
                    search: {
                        query: this.query,
                        fields: ['name'],
                    },
                    page: this.page,
                };

                return service.getWithFilters(filters).then(({ data, pagination = data.pagination }) => {
                    if (requestTimestamp === this.requestTimestamp) {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        data.data.forEach(option => this.projects.push(option));
                    }
                });
            },
            resetOptions() {
                this.projects = [];
            },
        },
        computed: {
            columns() {
                const columns = [
                    {
                        title: this.$t('field.project'),
                        key: 'name',
                    },
                    {
                        title: this.$t('field.members'),
                        key: 'users',
                        render: (h, { item }) => {
                            return h(TeamAvatars, {
                                props: {
                                    users: item.users || [],
                                },
                            });
                        },
                    },
                    {
                        title: this.$t('field.amount_of_tasks'),
                        key: 'tasks',
                        render: (h, { item }) => {
                            const amountOfTasks = item.tasks_count || 0;

                            return h(
                                'span',
                                i18n.tc('projects.amount_of_tasks', amountOfTasks, {
                                    count: amountOfTasks,
                                }),
                            );
                        },
                    },
                ];

                const actions = [
                    {
                        title: 'control.view',
                        icon: 'icon-eye',
                        onClick: (router, { item }) => {
                            this.$router.push(`/projects/view/${item.id}`);
                        },
                        renderCondition({ $store }) {
                            return true;
                        },
                    },
                    {
                        title: 'projects.members',
                        icon: 'icon-users',
                        onClick: (router, { item }) => {
                            this.$router.push(`/projects/${item.id}/members`);
                        },
                        renderCondition({ $can }, item) {
                            return $can('updateMembers', 'project', item);
                        },
                    },
                    {
                        title: 'control.edit',
                        icon: 'icon-edit',
                        onClick: (router, { item }, context) => {
                            this.$router.push(`/projects/edit/${item.id}`);
                        },
                        renderCondition: ({ $can }, item) => {
                            return $can('update', 'project', item);
                        },
                    },
                    {
                        title: 'control.delete',
                        actionType: 'error', // AT-UI action type,
                        icon: 'icon-trash-2',
                        onClick: (router, { item }, context) => {
                            this.$router.push(`/projects/delete/${item.id}`);
                        },
                        renderCondition: ({ $can }, item) => {
                            return $can('delete', 'project', item);
                        },
                    },
                ];

                columns.push({
                    title: this.$t('field.actions'),
                    render: (h, params) => {
                        let cell = h(
                            'div',
                            {
                                class: 'actions-column',
                            },
                            actions.map(item => {
                                if (
                                    typeof item.renderCondition !== 'undefined'
                                        ? item.renderCondition(this, params.item)
                                        : true
                                ) {
                                    return h(
                                        'AtButton',
                                        {
                                            props: {
                                                type: item.actionType || 'primary', // AT-ui button display type
                                                icon: item.icon || undefined, // Prepend icon to button
                                            },
                                            on: {
                                                click: () => {
                                                    item.onClick(this.$router, params, this);
                                                },
                                            },
                                            class: 'action-button',
                                            style: {
                                                margin: '0 10px 0 0',
                                            },
                                        },
                                        this.$t(item.title),
                                    );
                                }
                            }),
                        );

                        return cell;
                    },
                });

                return columns;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .projects {
        &__search {
            margin-bottom: $spacing-03;
        }

        .at-container {
            margin-bottom: 1rem;
            .table {
                &::v-deep .at-table {
                    &__cell {
                        width: 100%;
                        overflow-x: hidden;
                        padding-top: $spacing-05;
                        padding-bottom: $spacing-05;
                        border-bottom: 2px solid $blue-3;
                        position: relative;
                        z-index: 0;
                        &:last-child {
                            max-width: unset;
                        }
                    }

                    .actions-column {
                        display: flex;
                        flex-flow: row nowrap;
                    }

                    .action-button {
                        margin-right: 1em;
                    }
                }
            }
        }
    }
</style>
