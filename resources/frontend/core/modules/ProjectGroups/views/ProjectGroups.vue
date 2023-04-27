<template>
    <div class="project-groups">
        <h1 class="page-title">{{ $t('navigation.project-groups') }}</h1>

        <at-input
            v-model="query"
            type="text"
            :placeholder="'type to find group'"
            class="project-groups__search col-6"
            @input="onSearch"
        >
            <template slot="prepend">
                <i class="icon icon-search" />
            </template>
        </at-input>

        <div class="at-container">
            <div v-if="Object.keys(groups).length && !isDataLoading">
                <GroupCollapsable
                    :groups="groups"
                    @getTargetClickGroupAndChildren="getTargetClickGroupAndChildren"
                />
            </div>
            <div v-else class="at-container__inner no-data">
                <preloader v-if="isDataLoading" />
                <span>{{ $t('message.no_data') }}</span>
            </div>
        </div>
        <at-pagination
            :total="groupsTotal"
            :current="currentPage"
            :page-size="limit"
            @page-change="loadPage"
        />
    </div>
</template>

<script>
    import ProjectGroupsService from '@/services/resource/project-groups.service';
    import GroupCollapsable from '../components/GroupCollapsable';
    import Preloader from '@/components/Preloader';
    import debounce from 'lodash.debounce';

    export default {
        name: 'ProjectGroups',
        components: {
            Preloader,
            GroupCollapsable,
        },
        data() {
            return {
                groups: [],
                isDataLoading: false,
                groupsTotal: 0,
                limit: 10,
                service: new ProjectGroupsService(),
                totalPages: 0,
                currentPage: 1,
                query: '',
                requestTimestamp: null,
            };
        },
        async created() {
            this.search = debounce(this.search, 350);
            this.requestTimestamp = Date.now();
            this.search(this.requestTimestamp);
        },
        mounted() {
            this.loadPage();
        },
        computed: {
            hasNextPage() {
                return this.currentPage < this.totalPages;
            },
        },
        methods: {
            getSpaceByDepth: function (depth) {
                return ''.padStart(depth, '-');
            },
            onSearch() {
                this.search.cancel();
                this.requestTimestamp = Date.now();

                this.search(this.requestTimestamp);
            },
            async search() {
                this.totalPages = 0;
                this.currentPage = 0;
                this.resetOptions();
                this.lastSearchQuery = this.query;
                await this.$nextTick();
                await this.loadOptions();
                await this.$nextTick();
            },
            async loadOptions() {
                this.isDataLoading = true;
                const filters = {
                    search: { query: this.lastSearchQuery, fields: ['name'] },
                    with: [],
                    page: this.currentPage,
                    limit: this.limit,
                };

                if (this.query !== '') {
                    filters.with.push('groupParentsWithCountProjects')
                }

                return this.service.getWithFilters(filters).then(({ data, pagination }) => {
                    this.resetOptions()
                    this.groupsTotal = pagination.total
                    if (this.query == '') {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        data.forEach(option => this.groups.push(option));
                    } else {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        data.forEach(option => {
                            let breadCrumps = [];
                            option.group_parents_with_count_projects.forEach((el) => {
                                breadCrumps.push({
                                    name : el.name,
                                    id : el.id
                                });
                            });
                            breadCrumps.push({
                                name: option.name,
                                id: option.id,
                            });
                            option.breadCrumps = breadCrumps;
                            this.groups.push(option);
                        });
                    }

                    this.isDataLoading = false;
                });

            },
            async loadPage(page) {
                this.currentPage = page
                await this.loadOptions()
            },
            resetOptions() {
                this.groups = [];
            },
            getTargetClickGroupAndChildren(id) {
                this.service.getWithFilters({
                    where: {id},
                    with: ['descendantsWithDepthAndProjectsCount']
                }).then(({ data }) => {
                    this.resetOptions();
                    this.groups.push(data[0]);
                    data[0].descendants_with_depth_and_projects_count.forEach(element => {
                        this.groups.push(element);
                    });
                })
            }
        },
    };
</script>

<style lang="scss" scoped>
    .no-data {
        text-align: center;
        font-weight: bold;
        position: relative;
    }

    .project-groups {
        &__search {
            margin-bottom: $spacing-03;
        }
        &::v-deep {
            .at-container {
                overflow: hidden;
                margin-bottom: 1rem;
            }
        }
    }
</style>
