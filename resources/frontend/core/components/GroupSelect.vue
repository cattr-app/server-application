<template>
    <div ref="groupSelect" class="group-select" @click="onActive">
        <v-select
            v-model="model"
            :options="options"
            :filterable="false"
            label="name"
            :clearable="true"
            :reduce="option => option.name"
            :components="{ Deselect, OpenIndicator }"
            :dropdownShouldOpen="dropdownShouldOpen"
            @open="onOpen"
            @close="onClose"
            @search="onSearch"
            @option:selecting="handleSelecting"
        >
            <template #option="{ id, name, depth, current }">
                <span class="option" :class="{ 'option--current': current }">
                    <span class="option__text">
                        <span v-if="depth > 0" class="option__depth">{{ getSpaceByDepth(depth) }}</span>
                        <span class="option__label" :title="ucfirst(name)">{{ ucfirst(name) }}</span>
                        <span @click.stop>
                            <router-link
                                class="option__link"
                                :to="{ name: 'ProjectGroups.crud.groups.edit', params: { id: id } }"
                                target="_blank"
                                rel="opener"
                            >
                                <i class="icon icon-external-link" />
                            </router-link>
                        </span>
                    </span>
                </span>
            </template>

            <template #no-options="{ search }">
                <span>{{ $t('field.no_groups_found', { query: search }) }}</span>
            </template>

            <template #list-footer="{ search }">
                <at-button v-show="query !== ''" type="primary" class="no-option" size="small" @click="createGroup">
                    <span class="icon icon-plus-circle"></span>
                    {{ $t('field.fast_create_group', { query: search }) }}
                </at-button>

                <at-button type="primary" class="no-option" size="small" @click="navigateToCreateGroup">
                    <span class="icon icon-plus-circle"></span>
                    {{ $t('field.to_create_group', { query: search }) }}
                </at-button>

                <li v-show="hasNextPage" ref="load" class="option__infinite-loader">
                    {{ $t('field.loading_groups') }} <i class="icon icon-loader"></i>
                </li>
            </template>
        </v-select>
    </div>
</template>

<script>
    import ProjectGroupsService from '@/services/resource/project-groups.service';
    import { ucfirst } from '@/utils/string';
    import { mapGetters } from 'vuex';
    import vSelect from 'vue-select';
    import debounce from 'lodash/debounce';

    const service = new ProjectGroupsService();

    export default {
        name: 'GroupSelect',
        components: {
            vSelect,
        },
        props: {
            value: {
                type: [Object],
                default: null,
            },
        },
        data() {
            return {
                isSelectOpen: false,
                totalPages: 0,
                currentPage: 0,
                query: '',
                lastSearchQuery: '',
                Deselect: { render: h => h('i', { class: 'icon icon-x' }) },
            };
        },
        computed: {
            ...mapGetters('projectGroups', ['groups']),
            model: {
                get() {
                    return this.value;
                },
                set(option) {
                    if (typeof option === 'object') {
                        this.$emit('input', option);
                    }
                },
            },
            options() {
                if (!this.groups.has(this.lastSearchQuery)) {
                    return [];
                }

                return this.groups.get(this.lastSearchQuery).map(({ id, name, depth }) => ({
                    id,
                    name,
                    depth,
                    current: id === this.value?.id,
                }));
            },
            hasNextPage() {
                return this.currentPage < this.totalPages;
            },
            OpenIndicator() {
                return {
                    render: h =>
                        h('i', {
                            class: {
                                icon: true,
                                'icon-chevron-down': !this.isSelectOpen,
                                'icon-chevron-up': this.isSelectOpen,
                            },
                        }),
                };
            },
        },
        created() {
            this.search = debounce(this.search, 350);
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);
            document.addEventListener('click', this.onClickOutside);
        },
        beforeDestroy() {
            document.removeEventListener('click', this.onClickOutside);
        },
        methods: {
            ucfirst,
            navigateToCreateGroup() {
                this.$router.push({ name: 'ProjectGroups.crud.groups.new' });
            },
            dropdownShouldOpen() {
                if (this.isSelectOpen) {
                    this.onSearch(this.query);
                }

                return this.isSelectOpen;
            },
            async createGroup() {
                const query = this.query;
                this.query = '';
                this.onClose();

                try {
                    const { data } = await service.save({ name: query }, true);
                    this.model = data.data;

                    document.activeElement.blur();
                } catch (e) {
                    // TODO
                }
            },
            getSpaceByDepth: function (depth) {
                return ''.padStart(depth, '-');
            },
            onActive() {
                this.onOpen();
                this.$refs.groupSelect.parentElement.style.zIndex = 1;
            },
            async onOpen() {
                this.isSelectOpen = true;
                await this.$nextTick();
                this.observe();
            },
            onClose() {
                this.$refs.groupSelect.parentElement.style.zIndex = 0;
                this.isSelectOpen = false;
                this.observer.disconnect();
            },
            onSearch(query) {
                this.query = query;
                this.search.cancel();
                this.search();
            },
            async search() {
                this.observer.disconnect();

                this.totalPages = 0;
                this.currentPage = 0;
                this.lastSearchQuery = this.query;

                await this.$nextTick();
                await this.loadOptions();
                await this.$nextTick();

                this.observe();
            },
            handleSelecting(option) {
                this.onClose();
                this.model = option;
            },
            async infiniteScroll([{ isIntersecting, target }]) {
                if (isIntersecting) {
                    const ul = target.offsetParent;
                    const scrollTop = target.offsetParent.scrollTop;

                    await this.loadOptions();
                    await this.$nextTick();

                    ul.scrollTop = scrollTop;

                    this.observer.disconnect();
                    this.observe();
                }
            },
            observe() {
                if (this.isSelectOpen && this.$refs.load) {
                    this.observer.observe(this.$refs.load);
                }
            },
            async loadOptions() {
                this.$store.dispatch('projectGroups/loadGroups', {
                    query: this.lastSearchQuery,
                    page: this.currentPage,
                });

                this.currentPage++;
            },
            onClickOutside(e) {
                const opened = this.$el.contains(e.target);
                if (!opened) {
                    this.onClose();
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .group-select {
        border-radius: 5px;
        width: 100%;

        &::v-deep {
            .v-select {
                height: 100%;
            }

            .vs__selected-options {
                display: block;
                white-space: pre;
            }

            .vs__selected,
            .vs__search {
                display: inline;
            }

            .vs--open .vs__search {
                width: 100%;
            }

            .vs__actions {
                display: flex;
                font-size: 14px;
                margin-right: 8px;
                width: 30px;
                position: relative;
            }

            .vs__clear {
                display: block;
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

            .vs__dropdown-menu {
                scrollbar-width: none;

                &::-webkit-scrollbar {
                    display: none;
                }
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

        &__text {
            display: flex;
        }

        &__label {
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 0.5em;
        }

        &__link {
            font-size: 15px;
        }
    }

    .no-option {
        margin-top: 10px;
        cursor: pointer;
        display: block;
        width: 100%;

        & div {
            word-break: break-all;
            white-space: initial;
            line-height: 20px;

            &::before {
                margin-right: 5px;
            }
        }
    }
</style>
