<template>
    <div class="project-groups">
        <h1 class="page-title">{{ $t('groups.grid-title') }}</h1>
        <div class="project-groups__search-container">
            <at-input
                v-model="query"
                type="text"
                :placeholder="$t('message.group_search_input_placeholder')"
                class="project-groups__search-container__search col-6"
                @input="onSearch"
            >
                <template slot="prepend">
                    <i class="icon icon-search" />
                </template>
            </at-input>
            <div v-if="isGroupSelected" class="project-groups__selected-group">
                {{ groups[0].name }}
                <at-button
                    icon="icon-x"
                    circle
                    size="small"
                    class="project-groups__selected-group__clear"
                    @click="onSearch"
                ></at-button>
            </div>
        </div>
        <div class="at-container">
            <div v-if="Object.keys(groups).length && !isDataLoading">
                <GroupCollapsable
                    :groups="groups"
                    @getTargetClickGroupAndChildren="getTargetClickGroupAndChildren"
                    @reloadData="onSearch"
                />
                <div v-show="hasNextPage" ref="load" class="option__infinite-loader">
                    {{ $t('field.loading_groups') }} <i class="icon icon-loader" />
                </div>
            </div>
            <div v-else class="at-container__inner no-data">
                <preloader v-if="isDataLoading" />
                <span>{{ $t('message.no_data') }}</span>
            </div>
        </div>
    </div>
</template>

<script>
    import ProjectGroupsService from '@/services/resource/project-groups.service';
    import GroupCollapsable from '../components/GroupCollapsable';
    import Preloader from '@/components/Preloader';
    import debounce from 'lodash.debounce';

    const service = new ProjectGroupsService();

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
                totalPages: 0,
                currentPage: 0,
                query: '',
                isGroupSelected: false,
            };
        },
        async created() {
            this.search = debounce(this.search, 350);
            this.requestTimestamp = Date.now();
            this.search(this.requestTimestamp);
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);
        },
        computed: {
            hasNextPage() {
                return this.currentPage < this.totalPages;
            },
        },
        methods: {
            async infiniteScroll([{ isIntersecting, target }]) {
                if (isIntersecting) {
                    const requestTimestamp = +target.dataset.requestTimestamp;

                    if (requestTimestamp === this.requestTimestamp) {
                        await this.loadOptions(requestTimestamp);

                        await this.$nextTick();

                        this.observer.disconnect();
                        this.observe(requestTimestamp);
                    }
                }
            },
            onSearch() {
                this.isGroupSelected = false;

                this.requestTimestamp = Date.now();

                this.search(this.requestTimestamp);
            },
            async search(requestTimestamp) {
                this.observer.disconnect();
                this.totalPages = 0;
                this.currentPage = 0;
                this.resetOptions();
                await this.$nextTick();
                await this.loadOptions(requestTimestamp);
                await this.$nextTick();
                this.observe(requestTimestamp);
            },
            observe(requestTimestamp) {
                if (this.$refs.load) {
                    this.$refs.load.dataset.requestTimestamp = requestTimestamp;
                    this.observer.observe(this.$refs.load);
                }
            },
            async loadOptions() {
                const filters = {
                    search: { query: this.query, fields: ['name'] },
                    with: [],
                    page: this.currentPage + 1,
                    limit: this.limit,
                };

                if (this.query !== '') {
                    filters.with.push('groupParentsWithProjectsCount');
                }

                return service.getWithFilters(filters).then(({ data, pagination }) => {
                    this.groupsTotal = pagination.total;
                    if (this.query === '') {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        data.forEach(option => this.groups.push(option));
                    } else {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        data.forEach(option => {
                            let breadCrumbs = [];
                            option.group_parents_with_projects_count.forEach(el => {
                                breadCrumbs.push({
                                    name: `${el.name} (${el.projects_count})`,
                                    id: el.id,
                                });
                            });
                            option.breadCrumbs = breadCrumbs;
                            this.groups.push(option);
                        });
                    }
                });
            },
            resetOptions() {
                this.groups = [];
            },
            getTargetClickGroupAndChildren(id) {
                this.query = '';
                service
                    .getWithFilters({
                        where: { id },
                        with: ['descendantsWithDepthAndProjectsCount'],
                    })
                    .then(({ data, pagination }) => {
                        this.totalPages = pagination.totalPages;
                        this.currentPage = pagination.currentPage;
                        this.resetOptions();
                        this.groups.push(data[0]);
                        data[0].descendants_with_depth_and_projects_count.forEach(element => {
                            this.groups.push(element);
                        });
                        this.isGroupSelected = true;
                    });
            },
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
        &__search-container {
            display: flex;
            align-items: center;
            margin-bottom: $spacing-03;
        }
        &__selected-group {
            background: #ddd;
            border-radius: 90px/100px;
            padding: 5px 20px;
            margin-left: 15px;
            align-items: center;

            &__clear {
                margin-left: 10px;
                &:hover {
                    background: rgba(97, 144, 232, 0.6);
                }
            }
        }
        &::v-deep {
            .at-container {
                overflow: hidden;
                margin-bottom: 1rem;
            }
        }
    }
</style>
