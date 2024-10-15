<template>
    <div ref="groupSelect" class="group-select" @click="onActive">
        <div v-show="isActive === false">{{ model == '' ? $t('field.no_group_selected') : model }}</div>
        <v-select
            v-show="isActive === true"
            v-model="model"
            :options="options"
            :filterable="false"
            label="label"
            :clearable="true"
            :reduce="option => option.label"
            :components="{ Deselect, OpenIndicator }"
            :dropdownShouldOpen="dropdownShouldOpen"
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
                    <at-button v-show="query !== ''" type="primary" class="no-option" size="large" @click="createGroup">
                        <span class="icon icon-plus-circle"></span>
                        {{ $t('field.fast_create_group', { query: search }) }}
                    </at-button>
                </template>
                <template>
                    <at-button type="primary" class="no-option" size="large" @click="navigateToCreateGroup">
                        <span class="icon icon-plus-circle"></span>
                        {{ $t('field.to_create_group', { query: search }) }}
                    </at-button>
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
    import { mapGetters } from 'vuex';
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
            project: {
                type: Object,
                required: true,
            },
        },
        components: {
            vSelect,
        },
        data() {
            return {
                isActive: false,
                isSelectOpen: false,
                totalPages: 0,
                currentPage: 0,
                query: '',
                lastSearchQuery: '',
                localCurrentGroup: null,
            };
        },
        created() {
            this.search = debounce(this.search, 350);
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);

            const onClickOutside = e => {
                const opened = this.$el.contains(e.target);
                if (!opened) {
                    this.onClose();
                }
            };

            document.addEventListener('click', onClickOutside);
            this.$on('hook:beforeDestroy', () => document.removeEventListener('click', onClickOutside));
        },
        watch: {
            async valueAndQuery(newValue) {
                if (newValue.value === '') {
                    this.localCurrentGroup != null
                        ? (this.localCurrentGroup.current = false)
                        : (this.localCurrentGroup = null);
                    this.$emit('setCurrent', '');
                } else if (newValue.value !== this.currentGroup.name) {
                    this.$emit('setCurrent', {
                        id: this.localCurrentGroup.id,
                        name: this.localCurrentGroup.label,
                    });
                }

                if (newValue.value === '' && newValue.query === '') {
                    this.search();
                }
            },
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
                this.query = '';
                this.onClose();

                try {
                    const { data } = await service.save({ name: this.query }, true);
                    this.$emit('createGroup', {
                        id: data.data.id,
                        name: data.data.name,
                    });
                } catch (e) {
                    // TODO
                }
            },
            getSpaceByDepth: function (depth) {
                return ''.padStart(depth, '-');
            },
            onActive() {
                if (!this.project.can.update) {
                    return;
                }

                this.isActive = true;
                this.onOpen();
                this.$refs.groupSelect.parentElement.style.zIndex = 1;
            },
            async onOpen() {
                this.isSelectOpen = true;
                await this.$nextTick();
                this.observe();
            },
            onClose() {
                this.isActive = false;
                if (this.model == null) {
                    this.model = typeof this.currentGroup == 'object' ? this.currentGroup.name : this.currentGroup;
                }

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
                if (this.localCurrentGroup != null) {
                    this.localCurrentGroup.current = false;
                }

                option.current = true;
                this.localCurrentGroup = option;
                this.onClose();
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
            ...mapGetters('projectGroups', ['groups']),
            options() {
                if (!this.groups.has(this.lastSearchQuery)) {
                    return [];
                }

                return this.groups.get(this.lastSearchQuery).map(({ id, name, depth }) => ({
                    id,
                    label: name,
                    depth,
                    current: id === this.currentGroup?.id,
                }));
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

            .vs__dropdown-menu {
                width: 360px;
                &::-webkit-scrollbar {
                    display: none;
                }

                scrollbar-width: none;
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
