<template>
    <div class="crud" :class="crudClass">
        <div class="crud__header">
            <h1 class="page-title crud__title">{{ $t(title) }}</h1>
            <h4 class="crud__total">{{ $t('field.total') }} {{ totalItems }}</h4>
        </div>

        <div class="row crud__filters">
            <at-input
                v-if="filters.length"
                v-model="filterModel"
                type="text"
                :placeholder="filterPlaceholder"
                class="col-6 col-xs-24 crud__filter"
                @input="handleSearchInput"
            >
                <template slot="prepend">
                    <i class="icon icon-search"></i>
                </template>
            </at-input>

            <div class="col crud__control-items">
                <div v-if="visibleFilterFields && visibleFilterFields.length" class="crud__control-items__item">
                    <at-button icon="icon-filter" size="large" @click.prevent.stop="toggleFilterPopup" />

                    <div v-show="filterPopupVisible" class="crud__popup-filters">
                        <template v-for="filter of visibleFilterFields">
                            <div
                                v-show="!filter.fieldOptions || !filter.fieldOptions.hidden"
                                :key="filter.key + '_title'"
                                class="crud__popup-filter-title"
                            >
                                {{ $t(filter.label) }}
                            </div>

                            <at-select
                                v-if="filter.fieldOptions && filter.fieldOptions.type === 'select'"
                                v-show="!filter.fieldOptions || !filter.fieldOptions.hidden"
                                :key="filter.key"
                                v-model="filterFieldsModel[filter.key]"
                                type="text"
                                size="small"
                                class="crud__popup-filter"
                                :placeholder="$t(filter.placeholder)"
                                @input="onFilterFieldChange(filter.key, $event)"
                            >
                                <at-option
                                    v-for="(option, optionKey) of filter.fieldOptions.options"
                                    :key="optionKey"
                                    :value="option.value"
                                >
                                    {{ $t(option.label) }}
                                </at-option>
                            </at-select>

                            <UserSelect
                                v-else-if="filter.fieldOptions && filter.fieldOptions.type === 'user-select'"
                                v-show="!filter.fieldOptions || !filter.fieldOptions.hidden"
                                :key="filter.key"
                                v-model="filterFieldsModel[filter.key]"
                                size="small"
                                class="crud__popup-filter"
                                @loaded="onFilterLoaded(filter.key)"
                                @change="onUserSelectChange(filter.key, $event)"
                            ></UserSelect>

                            <ProjectSelect
                                v-else-if="filter.fieldOptions && filter.fieldOptions.type === 'project-select'"
                                v-show="!filter.fieldOptions || !filter.fieldOptions.hidden"
                                :key="filter.key"
                                v-model="filterFieldsModel[filter.key]"
                                size="small"
                                class="crud__popup-filter"
                                @loaded="onFilterLoaded(filter.key)"
                                @change="onProjectsChange(filter.key, $event)"
                            />

                            <StatusSelect
                                v-else-if="filter.fieldOptions && filter.fieldOptions.type === 'status-select'"
                                v-show="!filter.fieldOptions || !filter.fieldOptions.hidden"
                                :key="filter.key"
                                v-model="filterFieldsModel[filter.key]"
                                size="small"
                                class="crud__popup-filter"
                                @loaded="onFilterLoaded(filter.key)"
                                @change="onStatusesChange(filter.key, $event)"
                            />

                            <at-input
                                v-else
                                v-show="!filter.fieldOptions || !filter.fieldOptions.hidden"
                                :key="filter.key"
                                v-model="filterFieldsModel[filter.key]"
                                type="text"
                                size="small"
                                class="crud__popup-filter"
                                :placeholder="$t(filter.placeholder)"
                                @input="onFilterFieldChange(filter.key, $event)"
                            >
                                <template slot="prepend">
                                    <i class="icon icon-search"></i>
                                </template>
                            </at-input>
                        </template>
                    </div>
                </div>

                <template v-for="(control, key) of pageControls">
                    <template v-if="checkWithCtx(control.renderCondition)">
                        <at-checkbox
                            v-if="control.frontedType === 'checkbox'"
                            :key="control.key"
                            v-model="values[control.key]"
                            class="crud__control-items__item"
                            @on-change="handleWithCtx(control.onChange)"
                        >
                            {{ $t(control.label) }}
                        </at-checkbox>

                        <at-button
                            v-else
                            :key="key"
                            class="crud__control-items__item"
                            size="large"
                            :type="control.type"
                            :icon="control.icon"
                            @click="handleWithCtx(control.onClick)"
                            >{{ $t(control.label) }}
                        </at-button>
                    </template>
                </template>
            </div>
        </div>

        <div class="at-container">
            <div ref="tableWrapper" class="crud__table" :style="cssVarsForGridCols">
                <at-table ref="table" :key="columnsKey" size="large" :columns="columns" :data="displayableData" />
                <preloader v-if="isDataLoading" class="preloader" :is-transparent="true" />
            </div>
        </div>

        <at-pagination
            :total="totalItems"
            :current="page"
            :page-size="itemsPerPage"
            class="crud__pagination"
            @page-change="onPageChange"
        />
    </div>
</template>

<script>
    import Preloader from '@/components/Preloader';
    import ProjectSelect from '@/components/ProjectSelect';
    import StatusSelect from '@/components/StatusSelect';
    import UserSelect from '@/components/UserSelect';
    import { mapGetters } from 'vuex';

    const widthLessThan500MediaQuery = matchMedia('(max-width: 500px)');

    export default {
        name: 'GridView',
        components: {
            Preloader,
            ProjectSelect,
            StatusSelect,
            UserSelect,
        },
        data() {
            const { query, params } = this.$route;
            const { gridData, sortable } = this.$route.meta;

            let orderBy = null;
            if (sortable && gridData.columns.length) {
                const col = gridData.columns[0];

                orderBy = {
                    ...col,
                    title: this.$t(col.title),
                    direction: 'asc',
                };
            }

            const withParam = gridData.with;
            const whereParam = gridData.where || {};
            const withCount = gridData.withCount;
            const withSum = gridData.withSum;

            const filterFieldsModel = {};
            const fieldsToLoad = (gridData.filterFields || []).filter(f => f.saveToQuery).map(f => f.key);
            Object.keys(query).forEach(field => {
                if (fieldsToLoad.indexOf(field) !== -1) {
                    filterFieldsModel[field] = ['=', query[field]];
                }
            });

            const fieldsToLoadFromParams = (gridData.filterFields || []).map(f => f.key);
            Object.keys(params).forEach(field => {
                if (fieldsToLoadFromParams.indexOf(field) !== -1) {
                    filterFieldsModel[field] = ['=', params[field]];
                }
            });

            const filters = gridData.filters || [];
            const filterFields = gridData.filterFields || [];
            const loadedFilters = filterFields.reduce((obj, filter) => {
                const loaded =
                    ['user-select', 'project-select', 'status-select'].indexOf(filter.fieldOptions.type) === -1;

                return { ...obj, [filter.key]: loaded };
            }, {});

            return {
                title: gridData.title || '',
                filters,
                filterFields,
                loadedFilters,
                tableData: [],

                filterModel: this.$route.query.search,
                filterTimeout: null,
                filterFieldsTimeout: null,
                orderBy,
                filterPopupVisible: false,
                filterFieldsModel: { ...filterFieldsModel },

                service: gridData.service,

                pageControls: this.$route.meta.pageControls || [],

                page: +(this.$route.query.page || 1),
                totalItems: 0,
                itemsPerPage: 15,
                values: [],
                queryParams: {
                    with: withParam,
                    orderBy: gridData.orderBy,
                    where: whereParam,
                    withCount,
                    withSum,
                    page: this.$route.query.page,
                    search: {
                        query: this.$route.query.search,
                        fields: gridData.filters.map(filter => filter.referenceKey),
                    },
                },
                lastDeletedItem: [],

                isDataLoading: false,
                skipRouteUpdate: false,

                openActionsFor: null,
                hideColumns: widthLessThan500MediaQuery.matches,
            };
        },
        methods: {
            setHideColumns(e) {
                this.hideColumns = e.matches;
            },
            handleSearchInput() {
                clearTimeout(this.filterTimeout);

                this.filterTimeout = setTimeout(() => {
                    this.queryParams.search.query = this.filterModel;
                    const firstPage = 1;
                    this.handlePageChange(firstPage);
                    this.updateRoute();
                }, 500);
            },
            toggleFilterPopup() {
                this.filterPopupVisible = !this.filterPopupVisible;
            },
            showFilterPopup() {
                this.filterPopupVisible = true;
            },
            hideFilterPopup() {
                this.filterPopupVisible = false;
            },
            getFilterFieldKeys() {
                return this.visibleFilterFields.filter(f => f.saveToQuery).map(f => f.key);
            },
            getFilterFields() {
                const filters = {};
                const fieldsToSave = this.getFilterFieldKeys();
                Object.keys(this.filterFieldsModel).forEach(field => {
                    if (fieldsToSave.indexOf(field) !== -1 && typeof this.filterFieldsModel[field] !== 'undefined') {
                        filters[field] = this.filterFieldsModel[field];
                    }
                });

                return filters;
            },
            loadFilterFields() {
                const { query } = this.$route;
                const filters = {};
                const fieldsToLoad = this.getFilterFieldKeys();
                Object.keys(query).forEach(field => {
                    if (fieldsToLoad.indexOf(field) !== -1) {
                        filters[field] = query[field];
                    }
                });

                this.filterFieldsModel = filters;
            },
            updateQueryParams() {
                Object.keys(this.filterFieldsModel).forEach(field => {
                    if (
                        typeof this.filterFieldsModel[field] !== 'undefined' &&
                        this.filterFieldsModel[field].toString().length
                    ) {
                        const filter = this.filterFields.find(filter => filter.key === field);
                        if (filter && filter.fieldOptions && filter.fieldOptions.type === 'text') {
                            this.queryParams['where'][field] = ['like', `%${this.filterFieldsModel[field]}%`];
                        } else {
                            this.queryParams['where'][field] = this.filterFieldsModel[field];
                        }
                    } else {
                        delete this.queryParams['where'][field];
                    }
                });
            },
            filterFieldsData() {
                clearTimeout(this.filterFieldsTimeout);

                this.updateQueryParams();
                this.queryParams.page = 1;
                if (!this.filtersLoaded) {
                    return;
                }

                this.filterFieldsTimeout = setTimeout(() => this.fetchData(), 500);
            },
            onFilterFieldChange(key, data) {
                this.filterFieldsData();
                this.updateRoute();
            },
            onUserSelectChange(key, data) {
                if (data.length > 0) {
                    this.filterFieldsModel[key] = ['=', data];
                } else {
                    this.filterFieldsModel[key] = '';
                }

                this.filterFieldsData();
            },
            onProjectsChange(key, data) {
                if (data.length > 0) {
                    this.filterFieldsModel[key] = ['=', data];
                } else {
                    this.filterFieldsModel[key] = '';
                }

                this.filterFieldsData();
            },
            onStatusesChange(key, data) {
                if (data.length > 0) {
                    this.filterFieldsModel[key] = ['=', data];
                } else {
                    this.filterFieldsModel[key] = '';
                }

                this.filterFieldsData();
            },
            checkWithCtx(callback) {
                return callback ? callback(this) : true;
            },
            handleWithCtx(callback) {
                callback(this);
            },
            async onPageChange(page) {
                await this.handlePageChange(page);
                this.updateRoute();
            },
            handlePageChange(page) {
                if (this.queryParams.page === page) {
                    return;
                }

                this.queryParams.page = page;
                return this.fetchData();
            },
            async fetchData() {
                this.isDataLoading = true;

                const { queryParams, sortable, orderBy } = this;
                if (sortable && orderBy) {
                    queryParams['orderBy'] = [orderBy.key, orderBy.direction];
                }

                try {
                    const res = await this.service.getWithFilters(queryParams, { headers: { 'X-Paginate': 'true' } });
                    const { data, pagination } = res.data;

                    this.totalItems = pagination.total;
                    this.itemsPerPage = pagination.perPage;
                    this.page = pagination.currentPage;

                    this.tableData = data;
                } catch ({ response }) {
                    if (process.env.NODE_ENV === 'development') {
                        console.warn(response ? response : 'request is canceled');
                    }
                }

                this.isDataLoading = false;
            },
            handleClick(e) {
                if (!e.target.closest('.actions__toggle')) {
                    this.openActionsFor = null;
                }
                if (e.target.closest('.crud__popup-filters')) {
                    return;
                }

                if (this.filterPopupVisible) {
                    this.hideFilterPopup();
                }
            },
            handleResize() {
                const { table } = this.$refs;
                if (!table) {
                    return;
                }

                table.handleResize();
            },
            handleTableClick(e) {
                const { sortable, orderBy } = this;
                if (!sortable) {
                    return;
                }

                if (
                    !e.target.classList.contains('at-table__cell') ||
                    !e.target.classList.contains('at-table__column')
                ) {
                    return;
                }

                let column = null;
                for (let _column of this.columns) {
                    if (_column.title === e.target.textContent.trim()) {
                        column = _column;
                        break;
                    }
                }

                if (!column || !column.key) {
                    return;
                }

                if (orderBy && orderBy.key === column.key) {
                    const direction = orderBy.direction === 'asc' ? 'desc' : 'asc';
                    this.orderBy = { ...orderBy, direction };
                } else {
                    this.orderBy = { ...column, direction: 'asc' };
                }

                this.fetchData();
            },
            onView({ id }) {
                this.$router.push({ name: this.$route.meta.navigation.view, params: { id } });
            },
            onEdit({ id }) {
                this.$router.push({ name: this.$route.meta.navigation.edit, params: { id } });
            },
            async onDelete({ id }) {
                const isConfirm = await this.$CustomModal({
                    title: this.$t('notification.record.delete.confirmation.title'),
                    content: this.$t('notification.record.delete.confirmation.message'),
                    okText: this.$t('control.delete'),
                    cancelText: this.$t('control.cancel'),
                    showClose: false,
                    styles: {
                        'border-radius': '10px',
                        'text-align': 'center',
                        footer: {
                            'text-align': 'center',
                        },
                        header: {
                            padding: '16px 35px 4px 35px',
                            color: 'red',
                        },
                        body: {
                            padding: '16px 35px 4px 35px',
                        },
                    },
                    width: 320,
                    type: 'trash',
                    typeButton: 'error',
                });

                if (isConfirm !== 'confirm') {
                    return;
                }

                await this.service.deleteItem(id);
                this.$Notify({
                    type: 'success',
                    title: this.$t('notification.record.delete.success.title'),
                    message: this.$t('notification.record.delete.success.message'),
                });

                await this.fetchData();
            },
            updateRoute() {
                if (this.skipRouteUpdate) {
                    return;
                }

                const data = {
                    name: this.$route.name,
                    query: {
                        page: this.queryParams.page,
                        search: this.queryParams.search.query,
                        ...this.getFilterFields(),
                    },
                };

                this.$router.push(data);
            },
            onFilterLoaded(key) {
                this.loadedFilters = { ...this.loadedFilters, [key]: true };
                if (this.filtersLoaded) {
                    this.fetchData();
                }
            },
        },
        updated() {
            const { sortable, orderBy } = this;
            if (!sortable || !orderBy) {
                return;
            }

            if (typeof this.$refs.tableWrapper === 'undefined') {
                return;
            }

            const { tableWrapper } = this.$refs;

            const chevrons = tableWrapper.querySelectorAll('.at-table__cell.at-table__column > .chevron');
            chevrons.forEach(chevron => chevron.remove());

            let column = null;
            const columns = tableWrapper.querySelectorAll('.at-table__cell.at-table__column');
            for (let _column of columns) {
                if (_column.textContent.trim() === orderBy.title) {
                    column = _column;
                    break;
                }
            }

            if (!column) {
                return;
            }

            if (orderBy.direction === 'asc') {
                column.insertAdjacentHTML('beforeend', '<i class="icon icon-chevron-up chevron"></i>');
            } else {
                column.insertAdjacentHTML('beforeend', '<i class="icon icon-chevron-down chevron"></i>');
            }
        },
        computed: {
            ...mapGetters('user', ['user']),
            columnsKey() {
                // Used to forced update table when columns changed
                return this.columns.map(col => col.title).join(',');
            },
            columns() {
                const { gridData, sortable } = this.$route.meta;

                const columns = gridData.columns.map(col => {
                    col = { ...col, title: this.$t(col.title) };
                    // Used to edit statuses in company settings
                    if ('render' in col) {
                        col._render = col.render;
                        col.render = (h, params) => col._render(h, { ...params, gridView: this });
                    }

                    return col;
                });

                if (gridData.actions.length > 0 && columns.filter(t => t.title === 'field.actions').length === 0) {
                    columns.push({
                        title: this.$t('field.actions'),
                        render: (h, params) => {
                            let cell = h(
                                'div',
                                {
                                    class: 'actions-column',
                                },
                                [
                                    h('AtButton', {
                                        props: {
                                            type: 'primary',
                                            icon: params.item.id === this.openActionsFor ? 'icon-x' : 'icon-grid',
                                        },
                                        class: 'actions__toggle',
                                        on: {
                                            click: () => {
                                                if (this.openActionsFor === params.item.id) {
                                                    this.openActionsFor = null;
                                                } else {
                                                    this.openActionsFor = params.item.id;
                                                }
                                            },
                                        },
                                    }),
                                    h(
                                        'div',
                                        {
                                            class: {
                                                actions__wrapper: true,
                                                'actions__wrapper--active': this.openActionsFor === params.item.id,
                                            },
                                        },
                                        gridData.actions.map(item => {
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
                                                    },
                                                    this.$t(item.title),
                                                );
                                            }
                                        }),
                                    ),
                                ],
                            );

                            if (typeof gridData.actionsFilter !== 'undefined') {
                                return gridData.actionsFilter(h, cell, params);
                            }

                            return cell;
                        },
                    });
                }

                return columns.filter(column => {
                    if (this.hideColumns && column.hideForMobile) {
                        return false;
                    }
                    return this.checkWithCtx(column.renderCondition);
                });
            },
            visibleFilterFields() {
                return this.filterFields.filter(filter => {
                    const column = this.columns.find(column => column.key === filter.key);
                    if (column) {
                        return this.checkWithCtx(column.renderCondition);
                    }

                    return true;
                });
            },
            displayableData() {
                return this.tableData;
            },
            filterPlaceholder() {
                const filters = [...this.filters];
                const last = filters.pop();
                if (filters.length) {
                    const fields = filters.map(filter => this.$t(filter.filterName)).join(', ');
                    return this.$t('filter.enter-multiple', [fields, this.$t(last.filterName)]);
                } else {
                    return this.$t('filter.enter-single', [this.$t(last.filterName)]);
                }
            },
            crudClass() {
                const styles = {};
                if (typeof this.$route.meta.style !== 'undefined') {
                    styles[`crud_style_${this.$route.meta.style}`] = true;
                }

                if (this.sortable) {
                    styles['crud_sortable'] = true;
                }

                return styles;
            },
            sortable() {
                return !!this.$route.meta.sortable;
            },
            filtersLoaded() {
                const keys = Object.keys(this.loadedFilters);
                if (!keys.length) {
                    return true;
                }

                return keys.every(key => this.loadedFilters[key]);
            },
            cssVarsForGridCols() {
                const { gridData } = this.$route.meta;
                const numOfActions = gridData.actions.length;
                const actionsCol = num => (numOfActions > 0 ? `${numOfActions / num}fr` : '');
                const hiddenColumnsAmount = gridData.columns.filter(column => column.hideForMobile).length;
                return {
                    '--grid-columns-gt-1620': `repeat(${gridData.columns.length}, minmax(75px, 1fr)) ${actionsCol(1)}`,
                    '--grid-columns-lt-1620': `repeat(${gridData.columns.length}, minmax(75px, 1fr)) ${actionsCol(3)}`,
                    '--grid-columns-lt-1200': `repeat(${gridData.columns.length}, minmax(75px, 1fr)) 0.5fr`,
                    '--grid-columns-lt-500': `repeat(${gridData.columns.length - hiddenColumnsAmount}, minmax(75px, 1fr)) 0.5fr`,
                };
            },
        },
        async mounted() {
            this.loadFilterFields();
            this.updateQueryParams();
            if (this.filtersLoaded) {
                await this.fetchData();
            }

            window.addEventListener('click', this.handleClick);
            window.addEventListener('resize', this.handleResize);
            this.handleResize();

            if (this.$refs.tableWrapper) {
                this.$refs.tableWrapper.addEventListener('click', this.handleTableClick);
            }

            widthLessThan500MediaQuery.addEventListener('change', this.setHideColumns);

            // websocket
            this.websocketEnterChannel = this.$route.meta.gridData.websocketEnterChannel;
            this.websocketLeaveChannel = this.$route.meta.gridData.websocketLeaveChannel;

            if (typeof this.websocketEnterChannel !== 'undefined') {
                this.websocketEnterChannel(this.user.id, {
                    create: data => {
                        this.tableData.unshift(data.model);
                    },
                    edit: data => {
                        const rowIndex = this.tableData.findIndex(row => +row.id === +data.model.id);
                        if (rowIndex !== -1) {
                            this.$set(this.tableData, rowIndex, data.model);
                        }
                    },
                    destroy: data => {
                        const rowIndex = this.tableData.findIndex(row => +row.id === +data.model.id);
                        if (rowIndex !== -1) {
                            this.tableData.splice(rowIndex, 1);
                        }
                    },
                });
            }
        },
        watch: {
            async $route(to) {
                this.skipRouteUpdate = true;

                this.queryParams.page = to.query.page;
                this.queryParams.search.query = to.query.search;
                this.filterModel = to.query.search;
                this.loadFilterFields();
                this.updateQueryParams();
                await this.fetchData();

                this.skipRouteUpdate = false;
            },
        },
        beforeDestroy() {
            widthLessThan500MediaQuery.removeEventListener('change', this.setHideColumns);
            if (typeof this.websocketLeaveChannel !== 'undefined') {
                this.websocketLeaveChannel(this.user.id);
            }

            window.removeEventListener('click', this.handleClick);
            window.removeEventListener('resize', this.handleResize);

            this.$refs.tableWrapper.removeEventListener('click', this.handleTableClick);
        },
    };
</script>

<style lang="scss" scoped>
    .crud {
        &__header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: $layout-01;
        }

        &__total {
            color: $gray-2;
        }

        &__control-items {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;

            &__item {
                position: relative;
            }
        }

        &__popup-filters {
            position: absolute;
            top: calc(100% + #{$spacing-03});
            right: 0;

            background: #fff;
            border: 1px solid $gray-5;
            border-radius: 5px;
            box-shadow: 0px 0px 100px rgba(63, 51, 86, 0.05);

            display: block;

            min-width: 200px;

            padding: $spacing-03;

            z-index: 100;
        }

        &__popup-filter-title {
            font-weight: 600;
            font-size: $font-size-smer;
            margin-bottom: $spacing-02;
        }

        &__popup-filter {
            &:not(:last-child) {
                margin-bottom: $spacing-03;
            }

            &::v-deep .at-select__placeholder {
                color: #3f536e;
            }
        }

        &__pagination {
            display: flex;
            justify-content: flex-end;

            margin-left: auto;
        }

        &__filters {
            margin-bottom: $spacing-03;
            @media (max-width: 991px) {
                gap: 1rem;
                flex-direction: row-reverse;
            }
        }

        &__filter {
            &::v-deep {
                .at-input-group__prepend {
                    border: 1px solid $gray-5;
                    border-right: 0;
                }

                .at-input__original {
                    border: 1px solid $gray-5;
                }
            }
        }

        &__column-filters {
            display: flex;
            flex-flow: row nowrap;

            z-index: 1;
        }

        &__column-filter {
            padding: 0.5rem;

            &::v-deep {
                .at-input-group__prepend {
                    border: 1px solid $gray-5;
                    border-right: 0;
                }

                .at-input__original,
                .at-select__selection {
                    border: 1px solid $gray-5;
                }
            }
        }

        &__table {
            position: relative;

            &::v-deep .at-table {
                table {
                    border-radius: $border-radius-lger;
                    display: grid;
                    overflow: unset;
                }

                tr {
                    display: grid;
                    grid-template-columns: var(--grid-columns-gt-1620);
                    @media (max-width: 1620px) {
                        grid-template-columns: var(--grid-columns-lt-1620);
                    }
                    @media (max-width: 1200px) {
                        grid-template-columns: var(--grid-columns-lt-1200);
                    }
                    @media (max-width: 500px) {
                        grid-template-columns: var(--grid-columns-lt-500);
                    }

                    th {
                        background: #fff;
                        color: #c4c4cf;
                    }
                }
                &__thead {
                    overflow: hidden;
                    border-radius: 20px 20px 0 0;
                }

                &__content {
                    border: 0;
                }

                &__tbody {
                    tr:last-child .at-table__cell {
                        border-bottom: 0;
                        border-radius: 0 0 20px 20px;
                    }
                }

                &__cell {
                    display: flex;
                    align-items: center;
                    border-bottom: 2px solid $blue-3;

                    position: relative;
                }

                &__cell-bg {
                    position: absolute;
                    display: block;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: -1;
                }

                .actions-column {
                    position: relative;
                    .actions__toggle {
                        display: none;
                        @media (max-width: 1200px) {
                            display: inline-block;
                        }
                    }
                    .actions__wrapper {
                        display: flex;
                        flex-direction: row;
                        flex-wrap: wrap;
                        gap: 1em;
                        @media (max-width: 1200px) {
                            display: none;
                            position: absolute;
                            //margin-top: 1em;
                            &--active {
                                display: flex;
                                right: 100%;
                                background: $bg-color;
                                border: 1px solid $black-900;
                                border-radius: 20px;
                                padding: 1em;
                                z-index: 1;
                                margin-right: 1em;
                                top: -1em;
                            }
                        }
                    }
                }

                .action-button {
                    @media (min-width: 1201px) and (max-width: 1620px) {
                        .at-btn__text {
                            display: none;
                        }
                    }
                }
            }
        }

        &_style_compact {
            &::v-deep .at-table__cell {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }
        }

        &_sortable {
            &::v-deep .at-table__cell.at-table__column {
                cursor: pointer !important;
            }
        }

        &::v-deep {
            .primary-border .at-btn--primary {
                border-color: white;
            }

            .error-border .at-btn--error {
                border-color: white;
            }
        }
    }

    .preloader {
        border-radius: $border-radius-lger;
    }

    .at-container ::v-deep {
        margin-bottom: $layout-01;
    }
</style>

<style lang="scss">
    .icon-bar-chart-2 {
        display: inline-block;
        transform: rotate(180deg);
    }
</style>
