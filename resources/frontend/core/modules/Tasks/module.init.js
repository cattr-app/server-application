import cloneDeep from 'lodash/cloneDeep';
import TasksService from '@/services/resource/task.service';
import ProjectsService from '@/services/resource/project.service';
import StatusService from '@/services/resource/status.service';
import { ModuleLoaderInterceptor } from '@/moduleLoader';
import UserAvatar from '@/components/UserAvatar';
import UserSelect from '@/components/UserSelect';
import PrioritySelect from '@/components/PrioritySelect';
import i18n from '@/i18n';
import { getTextColor } from '@/utils/color';
import { formatDate, formatDurationString } from '@/utils/time';
import { VueEditor } from 'vue2-editor';
import TaskComments from './components/TaskComments';
import TaskHistory from './components/TaskHistory';
import TimeEstimate from './components/TimeEstimate.vue';
import DateInput from './components/DateInput';
import { store as rootStore } from '@/store';
import moment from 'moment-timezone';
import { hasRole } from '@/utils/user';
import Vue from 'vue';
import ResourceSelect from '@/components/ResourceSelect.vue';
import PhaseSelect from './components/PhaseSelect';
import RelationsSelector from './components/RelationsSelector';
import Attachments from './components/Attachments.vue';

export const ModuleConfig = {
    routerPrefix: 'tasks',
    loadOrder: 20,
    moduleName: 'Tasks',
};

export function init(context, router) {
    let routes = {};

    ModuleLoaderInterceptor.on('Core', m => {
        m.routes.forEach(route => {
            if (route.name.search('users.view') > 0) {
                routes.usersView = route.name;
            }
        });
    });

    ModuleLoaderInterceptor.on('Projects', m => {
        m.routes.forEach(route => {
            if (route.name.search('view') > 0) {
                routes.projectsView = route.name;
            }
        });
    });

    const tasksContext = cloneDeep(context);
    //tasksContext.routerPrefix = 'projects/:project_id/tasks/list';

    const crud = tasksContext.createCrud('tasks.crud-title', 'tasks', TasksService, {
        with: [
            'can',
            'priority',
            'project',
            'phase:id,name',
            'parents',
            'children',
            'users',
            'status',
            'workers',
            'workers.user:id,full_name',
            'attachmentsRelation',
            'attachmentsRelation.user:id,full_name',
        ],
        withSum: [
            ['workers as total_spent_time', 'duration'],
            ['workers as total_offset', 'offset'],
        ],
    });

    const crudViewRoute = crud.view.getViewRouteName();
    const crudEditRoute = crud.edit.getEditRouteName();
    const crudNewRoute = crud.new.getNewRouteName();

    const navigation = { view: crudViewRoute, edit: crudEditRoute, new: crudNewRoute };

    crud.view.addToMetaProperties('titleCallback', ({ values }) => values.task_name, crud.view.getRouterConfig());
    crud.view.addToMetaProperties('navigation', navigation, crud.view.getRouterConfig());

    crud.new.addToMetaProperties('permissions', 'tasks/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties('navigation', navigation, crud.new.getRouterConfig());

    crud.edit.addToMetaProperties('permissions', 'tasks/edit', crud.edit.getRouterConfig());

    const grid = tasksContext.createGrid('tasks.grid-title', 'tasks', TasksService, {
        with: ['priority', 'project', 'users', 'status', 'can'],
        withSum: [
            ['workers as total_spent_time', 'duration'],
            ['workers as total_offset', 'offset'],
        ],
    });
    grid.addToMetaProperties('navigation', navigation, grid.getRouterConfig());

    const fieldsToShow = [
        {
            key: 'status',
            label: 'field.status',
            render: (h, { currentValue }) => {
                return h('span', typeof currentValue !== 'undefined' && currentValue !== null ? currentValue.name : '');
            },
        },
        {
            key: 'status',
            label: 'field.active',
            render: (h, { currentValue }) => {
                return h(
                    'span',
                    typeof currentValue !== 'undefined' && currentValue !== null && currentValue.active
                        ? i18n.t('control.yes')
                        : i18n.t('control.no'),
                );
            },
        },
        {
            key: 'project',
            label: 'field.project',
            render: (h, { currentValue }) => {
                return h(
                    'router-link',
                    {
                        props: {
                            to: {
                                name: routes.projectsView,
                                params: { id: currentValue.id },
                            },
                        },
                    },
                    currentValue.name,
                );
            },
        },
        {
            key: 'phase',
            label: 'field.phase',
            render: (h, { currentValue }) => {
                return h('span', {}, [currentValue?.name ?? i18n.t('tasks.unset_phase')]);
            },
        },
        {
            label: 'tasks.relations.title',
            key: 'relations',
            render: (h, data) => {
                return h(RelationsSelector, {
                    props: {
                        parents: Array.isArray(data.values.parents) ? data.values.parents : [],
                        children: Array.isArray(data.values.children) ? data.values.children : [],
                        showControls: false,
                    },
                });
            },
        },
        {
            key: 'priority',
            label: 'field.priority',
            render: (h, { currentValue }) => {
                if (!currentValue) {
                    return null;
                }

                if (!currentValue.color) {
                    return h('span', {}, [currentValue.name]);
                }

                return h(
                    'span',
                    {
                        style: {
                            display: 'flex',
                            alignItems: 'center',
                        },
                    },
                    [
                        h('span', {
                            style: {
                                display: 'inline-block',
                                background: currentValue.color,
                                borderRadius: '4px',
                                width: '16px',
                                height: '16px',
                                margin: '0 4px 0 0',
                            },
                        }),
                        h('span', {}, [currentValue.name]),
                    ],
                );
            },
        },
        {
            key: 'users',
            label: 'field.users',
            render: (h, data) => {
                if (!hasRole(router.app.$store.getters['user/user'], 'admin')) {
                    return h(
                        'ul',
                        {},
                        data.currentValue.map(item => h('li', item.full_name)),
                    );
                }

                return h(
                    'ul',
                    {},
                    data.currentValue.map(item =>
                        h('li', {}, [
                            h(
                                'router-link',
                                {
                                    props: {
                                        to: {
                                            name: routes.usersView,
                                            params: { id: item.id },
                                        },
                                    },
                                },
                                item.full_name,
                            ),
                        ]),
                    ),
                );
            },
        },
        {
            key: 'description',
            label: 'field.description',
            render: (h, props) => {
                if (!props.currentValue) {
                    return;
                }

                return h('div', {
                    class: { 'ql-editor': true },
                    domProps: {
                        innerHTML: props.currentValue,
                    },
                    style: {
                        padding: 0,
                        'overflow-y': 'hidden',
                    },
                });
            },
        },
        {
            key: 'attachments_relation',
            label: 'field.attachments',
            render: (h, data) => {
                return h(Attachments, {
                    props: {
                        attachments: Array.isArray(data.currentValue) ? data.currentValue : [],
                        showControls: false,
                    },
                });
            },
        },
        {
            key: 'url',
            label: 'field.source',
            render: (h, props) => {
                if (props.currentValue && props.currentValue.length && props.currentValue.toLowerCase() !== 'url') {
                    return h(
                        'a',
                        {
                            attrs: {
                                href: props.currentValue,
                                target: '_blank',
                            },
                        },
                        props.currentValue,
                    );
                } else {
                    return h('span', {}, i18n.t('tasks.source.internal'));
                }
            },
        },
        {
            key: 'created_at',
            label: 'field.created_at',
            render: (h, props) => h('span', formatDate(props.currentValue)),
        },
        {
            key: 'total_spent_time',
            label: 'field.total_spent',
            render: (h, props) => {
                const tags = [];
                const timeWithOffset = +props.values.total_spent_time + +props.values.total_offset;
                if (props.values.estimate != null && timeWithOffset > props.values.estimate) {
                    tags.push(
                        h(
                            'at-tag',
                            {
                                attrs: { color: 'warning' },
                            },
                            i18n.t('tasks.estimate--overtime'),
                        ),
                    );
                }
                const tagsWrapper = tags.length > 0 ? h('div', tags) : '';

                return h('span', [formatDurationString(timeWithOffset > 0 ? timeWithOffset : 0), tagsWrapper]);
            },
        },
        {
            key: 'estimate',
            label: 'field.estimate',
            render: (h, props) => {
                let estimate = i18n.t('tasks.unset_estimate');
                if (props.currentValue != null) {
                    estimate = formatDurationString(props.currentValue);
                }
                return h('span', estimate);
            },
        },
        {
            key: 'start_date',
            label: 'field.start_date',
            render: (h, props) => {
                let date = i18n.t('tasks.unset_start_date');
                const userTimezone = moment.tz.guess();
                const companyTimezone = rootStore.getters['user/companyData'].timezone;
                if (
                    props.currentValue != null &&
                    typeof props.currentValue === 'string' &&
                    typeof companyTimezone === 'string'
                ) {
                    date =
                        formatDate(moment.utc(props.currentValue).tz(companyTimezone, true).tz(userTimezone)) +
                        ` (GMT${moment.tz(userTimezone).format('Z')})`;
                }
                return h('span', date);
            },
        },
        {
            key: 'due_date',
            label: 'field.due_date',
            render: (h, props) => {
                let date = i18n.t('tasks.unset_due_date');
                const userTimezone = moment.tz.guess();
                const companyTimezone = rootStore.getters['user/companyData'].timezone;
                if (
                    props.currentValue != null &&
                    typeof props.currentValue === 'string' &&
                    typeof companyTimezone === 'string'
                ) {
                    date =
                        formatDate(moment.utc(props.currentValue).tz(companyTimezone, true).tz(userTimezone)) +
                        ` (GMT${moment.tz(userTimezone).format('Z')})`;
                }

                const tags = [];
                if (
                    typeof companyTimezone === 'string' &&
                    props.currentValue != null &&
                    moment.utc(props.currentValue).tz(companyTimezone, true).isBefore(moment())
                ) {
                    tags.push(
                        h(
                            'at-tag',
                            {
                                attrs: { color: 'error' },
                            },
                            i18n.t('tasks.due_date--overdue'),
                        ),
                    );
                }
                const tagsWrapper = tags.length > 0 ? h('div', tags) : '';

                return h('span', [date, tagsWrapper]);
            },
        },
        {
            key: 'forecast_completion_date',
            label: 'calendar.task.forecast_completion_date',
            render: (h, props) => {
                let date = '—';
                const userTimezone = moment.tz.guess();
                const companyTimezone = rootStore.getters['user/companyData'].timezone;
                if (
                    props.currentValue != null &&
                    typeof props.currentValue === 'string' &&
                    typeof companyTimezone === 'string'
                ) {
                    date =
                        formatDate(moment.utc(props.currentValue).tz(companyTimezone, true).tz(userTimezone)) +
                        ` (GMT${moment.tz(userTimezone).format('Z')})`;
                }
                return h('span', date);
            },
        },
        {
            key: 'workers',
            label: 'tasks.spent_by_user',
            render: (h, props) => {
                const tableData = [];
                const globalTimeWithOffset = +props.values.total_spent_time + +props.values.total_offset;
                Object.keys(props.currentValue).forEach(k => {
                    const timeWithOffset = +props.currentValue[k].duration + +props.currentValue[k].offset;
                    props.currentValue[k].time = formatDurationString(timeWithOffset);
                    props.currentValue[k].full_name = props.currentValue[k].user.full_name;
                    if (timeWithOffset > 0 && globalTimeWithOffset > 0) {
                        tableData.push(props.currentValue[k]);
                    }
                });
                return h('AtTable', {
                    props: {
                        columns: [
                            {
                                title: i18n.t('field.user'),
                                render: (h, { item }) => {
                                    if (!hasRole(router.app.$store.getters['user/user'], 'admin')) {
                                        return h('span', item.full_name);
                                    }

                                    return h(
                                        'router-link',
                                        {
                                            props: {
                                                to: {
                                                    name: 'Users.crud.users.view',
                                                    params: { id: item.user_id },
                                                },
                                            },
                                        },
                                        item.full_name,
                                    );
                                },
                            },
                            {
                                key: 'time',
                                title: i18n.t('field.time'),
                            },
                        ],
                        data: tableData,
                    },
                });
            },
        },
        {
            key: 'history',
            label: 'field.history',
            render: (h, props) => {
                return h(TaskHistory, { props: { task: props.values } });
            },
        },
    ];
    // {
    //     key: 'comments',
    //     label: 'field.comments',
    //     render: (h, props) => {
    //         return h(TaskComments, {
    //             props: { task: props.values },
    //         });
    //     },
    // },

    const fieldsToFill = [
        {
            key: 'id',
            displayable: false,
        },
        {
            label: 'field.project',
            key: 'project_id',
            render: (h, props) => {
                const value = typeof props.currentValue === 'number' ? props.currentValue : null;
                return h(ResourceSelect, {
                    props: {
                        value,
                        service: new ProjectsService(),
                    },
                    on: {
                        input: function (value) {
                            props.setValue('project_phase_id', null);
                            props.inputHandler(value);
                        },
                    },
                });
            },
            required: true,
        },

        {
            key: 'project_phase_id',
            label: 'field.phase',
            render: (h, props) => {
                const value = typeof props.currentValue === 'number' ? props.currentValue : '';
                const projectId = typeof props.values.project_id === 'number' ? props.values.project_id : 0;
                return h(PhaseSelect, {
                    props: {
                        value,
                        projectId,
                    },
                    on: {
                        input: function (value) {
                            props.inputHandler(value);
                        },
                    },
                });
            },
        },
        {
            label: 'field.task_name',
            key: 'task_name',
            type: 'input',
            required: true,
            placeholder: 'field.name',
        },
        {
            label: 'field.description',
            key: 'description',
            required: true,
            render: (h, props) => {
                return h(VueEditor, {
                    props: {
                        useMarkdownShortcuts: true,
                        editorToolbar: [
                            [
                                {
                                    header: [false, 1, 2, 3, 4, 5, 6],
                                },
                            ],
                            ['bold', 'italic', 'underline', 'strike'],
                            [
                                {
                                    list: 'ordered',
                                },
                                {
                                    list: 'bullet',
                                },
                                {
                                    list: 'check',
                                },
                            ],
                            [
                                {
                                    indent: '-1',
                                },
                                {
                                    indent: '+1',
                                },
                            ],
                            [
                                {
                                    color: [],
                                },
                                {
                                    background: [],
                                },
                            ],
                            ['link'],
                            ['clean'],
                        ],
                        value: props.values.description,
                        placeholder: i18n.t('field.description'),
                    },
                    on: {
                        input: function (text) {
                            props.inputHandler(text);
                        },
                    },
                });
            },
        },
        {
            key: 'attachments_relation',
            label: 'field.attachments',
            render: (h, data) => {
                return h(Attachments, {
                    props: {
                        attachments: Array.isArray(data.currentValue) ? data.currentValue : [],
                        showControls: true,
                    },
                    on: {
                        change(attachments) {
                            data.inputHandler(attachments);
                            data.setValue(
                                'attachmentsRelation',
                                attachments.filter(el => !el.toDelete).map(el => el.id),
                            );
                            data.setValue(
                                'attachmentsToRemove',
                                attachments.filter(el => el.toDelete).map(el => el.id),
                            );
                            // mitigate validation issues for empty array
                            if (data.values.attachmentsRelation.length === 0) {
                                delete data.values.attachmentsRelation;
                            }
                            if (data.values.attachmentsToRemove.length === 0) {
                                delete data.values.attachmentsToRemove;
                            }
                        },
                    },
                });
            },
        },
        {
            label: 'field.important',
            tooltipValue: 'tooltip.task_important',
            key: 'important',
            type: 'checkbox',
            initialValue: false,
        },
        {
            key: 'estimate',
            label: 'field.estimate',
            render: (h, data) => {
                const value = typeof data.currentValue === 'number' ? data.currentValue : null;
                return h(TimeEstimate, {
                    props: { value },
                    on: {
                        input: function (seconds) {
                            data.inputHandler(seconds);
                        },
                    },
                });
            },
        },
        {
            label: 'field.start_date',
            key: 'start_date',
            render: (h, props) => {
                const value = typeof props.currentValue === 'string' ? props.currentValue : null;

                return h(DateInput, {
                    props: {
                        inputHandler: props.inputHandler,
                        value,
                    },
                });
            },
        },
        {
            label: 'field.due_date',
            key: 'due_date',
            render: (h, props) => {
                const value = typeof props.currentValue === 'string' ? props.currentValue : null;

                return h(DateInput, {
                    props: {
                        inputHandler: props.inputHandler,
                        value,
                    },
                });
            },
        },
        {
            label: 'field.users',
            key: 'users',
            render: (h, props) => {
                const value =
                    typeof props.values.users !== 'undefined'
                        ? props.values.users.map(user => (typeof user === 'object' ? user.id : +user))
                        : [];

                return h(UserSelect, {
                    props: {
                        value,
                        localStorageKey: 'user-select.task',
                    },
                    on: {
                        change: function (value) {
                            props.inputHandler(value);
                        },
                    },
                });
            },
        },
        {
            label: 'field.priority',
            key: 'priority_id',
            render: (h, data) => {
                let value = '';
                if (typeof data.currentValue === 'number' || typeof data.currentValue === 'string') {
                    value = data.currentValue;
                }

                return h(PrioritySelect, {
                    props: {
                        value,
                        clearable: false,
                    },
                    on: {
                        input(value) {
                            data.inputHandler(value);
                        },
                    },
                });
            },
            required: true,
        },
        {
            label: 'field.status',
            key: 'status_id',
            type: 'resource-select',
            service: new StatusService(),
            required: true,
        },
    ];

    crud.view.addField(fieldsToShow);
    crud.edit.addField(fieldsToFill);
    crud.new.addField(fieldsToFill);

    const getCellStyle = item => {
        return typeof item.priority !== 'undefined' && item.priority !== null
            ? { color: getTextColor(item.priority.color) }
            : {};
    };

    const makeCellBg = (h, cell, item) => {
        if (typeof item.priority !== 'undefined' && item.priority !== null && item.priority.color !== null) {
            return h('span', {}, [
                cell,
                h(
                    'span',
                    {
                        class: ['at-table__cell-bg'],
                        style: { background: item.priority.color },
                    },
                    [],
                ),
            ]);
        }

        return cell;
    };

    grid.addColumn([
        {
            title: 'field.task',
            key: 'task_name',
            render: (h, { item }) => {
                const classes = ['tasks-grid__task'];
                if (!item.status || !item.status.active) {
                    classes.push('tasks-grid__task--inactive');
                }

                const companyTimezone = rootStore.getters['user/companyData'].timezone;

                let tags = [];
                if (
                    typeof companyTimezone === 'string' &&
                    item.due_date != null &&
                    moment.utc(item.due_date).tz(companyTimezone, true).isBefore(moment())
                ) {
                    tags.push(
                        h(
                            'at-tag',
                            {
                                class: ['tasks-grid__tag', 'tasks-grid__tag--overdue'],
                                attrs: { color: 'error' },
                            },
                            i18n.t('tasks.due_date--overdue'),
                        ),
                    );
                }

                const timeWithOffset = +item.total_spent_time + +item.total_offset;

                if (item.estimate != null && timeWithOffset > item.estimate) {
                    tags.push(
                        h(
                            'at-tag',
                            {
                                class: ['tasks-grid__tag', 'tasks-grid__tag--overtime'],
                                attrs: { color: 'warning' },
                            },
                            i18n.t('tasks.estimate--overtime'),
                        ),
                    );
                }

                const tagsWrapper =
                    tags.length > 0
                        ? h(
                              'div',
                              {
                                  class: ['tasks-grid__tags'],
                              },
                              tags,
                          )
                        : '';

                const cell = [
                    h(
                        'span',
                        {
                            class: classes,
                            style: getCellStyle(item),
                            attrs: { title: item.task_name },
                        },
                        item.task_name,
                    ),
                    tagsWrapper,
                ];

                return makeCellBg(h, cell, item);
            },
        },
        {
            title: 'field.project',
            key: 'project',
            render: (h, { item }) => {
                let projectName = '';

                if (typeof item.project !== 'undefined' && item.project !== null) {
                    projectName = item.project.name;
                }

                const cell = h(
                    'span',
                    {
                        class: 'tasks-grid__project',
                        style: getCellStyle(item),
                        attrs: { title: projectName },
                    },
                    projectName,
                );

                return makeCellBg(h, cell, item);
            },
        },
        {
            title: 'field.users',
            key: 'users',
            hideForMobile: true,
            render: (h, { item }) => {
                const users = item.users;
                if (!users) {
                    return makeCellBg(h, null, item);
                }

                const cell = h('div', { class: ['flex', 'flex-gap', 'flex-wrap'] }, [
                    users.map(user =>
                        h(
                            'AtTooltip',
                            {
                                props: {
                                    placement: 'top',
                                    content: user.full_name,
                                },
                            },
                            [
                                h(UserAvatar, {
                                    props: {
                                        user,
                                        showTooltip: true,
                                    },
                                }),
                            ],
                        ),
                    ),
                ]);

                return makeCellBg(h, cell, item);
            },
        },
    ]);

    const websocketLeaveChannel = id => Vue.prototype.$echo.leave(`tasks.${id}`);
    const websocketEnterChannel = (id, handlers) => {
        const channel = Vue.prototype.$echo.private(`tasks.${id}`);
        for (const action in handlers) {
            channel.listen(`.tasks.${action}`, handlers[action]);
        }
    };

    grid.addToMetaProperties('gridData.websocketEnterChannel', websocketEnterChannel, grid.getRouterConfig());
    grid.addToMetaProperties('gridData.websocketLeaveChannel', websocketLeaveChannel, grid.getRouterConfig());

    crud.view.addToMetaProperties('pageData.websocketEnterChannel', websocketEnterChannel, crud.view.getRouterConfig());
    crud.view.addToMetaProperties('pageData.websocketLeaveChannel', websocketLeaveChannel, crud.view.getRouterConfig());

    grid.addToMetaProperties(
        'gridData.actionsFilter',
        (h, cell, { item }) => {
            if (typeof item.priority !== 'undefined' && item.priority !== null) {
                if (/^#6C6CFF$/i.test(item.priority.color)) {
                    cell = h('span', { class: 'primary-border' }, [cell]);
                } else if (/^#FF5569$/i.test(item.priority.color)) {
                    cell = h('span', { class: 'error-border' }, [cell]);
                }
            }

            return makeCellBg(h, cell, item);
        },
        grid.getRouterConfig(),
    );

    grid.addFilter([
        {
            filterName: 'filter.fields.task_name',
            referenceKey: 'task_name',
        },
    ]);

    grid.addFilterField([
        {
            key: 'project_id',
            label: 'tasks.projects',
            fieldOptions: {
                type: 'project-select',
            },
        },
        {
            key: 'users.id',
            label: 'tasks.users',
            fieldOptions: { type: 'user-select' },
        },
        {
            key: 'status.id',
            label: 'tasks.status',
            fieldOptions: { type: 'status-select' },
        },
    ]);

    const relationsRouteName = context.getModuleRouteName() + '.relations';
    context.addRoute([
        {
            path: `/${context.routerPrefix}/:id/relations`,
            name: relationsRouteName,
            component: () => import('./views/TaskRelations.vue'),
            meta: {
                auth: true,
            },
        },
    ]);

    grid.addAction([
        {
            title: 'control.view',
            icon: 'icon-eye',
            onClick: (router, { item }, context) => {
                context.onView(item);
            },
            renderCondition() {
                // User always can view assigned tasks
                return true;
            },
        },
        {
            title: 'tasks.relations.title',
            icon: 'icon-corner-down-right',
            onClick: (router, { item }) => {
                router.push({ name: relationsRouteName, params: { id: item.id } });
            },
            renderCondition({ $can }, item) {
                return $can('update', 'task', item);
            },
        },
        {
            title: 'control.edit',
            icon: 'icon-edit',
            onClick: (router, { item }, context) => {
                context.onEdit(item);
            },
            renderCondition: ({ $can }, item) => {
                return $can('update', 'task', item);
            },
        },
        {
            title: 'control.delete',
            actionType: 'error',
            icon: 'icon-trash-2',
            onClick: async (router, { item }, context) => {
                context.onDelete(item);
            },
            renderCondition: ({ $can }, item) => {
                return $can('delete', 'task', item);
            },
        },
    ]);

    grid.addPageControls([
        {
            label: 'control.create',
            type: 'primary',
            icon: 'icon-edit',
            onClick: ({ $router }) => {
                $router.push({ name: crudNewRoute });
            },
            renderCondition: ({ $can }) => {
                return $can('create', 'task');
            },
        },
        {
            label: 'control.kanban-board',
            type: 'default',
            onClick: ({ $router, $route }) => {
                $router.push(`/projects/${$route.params.project_id}/tasks/kanban`);
            },
            renderCondition: () => false,
        },
        {
            label: 'control.back',
            type: 'default',
            onClick: ({ $router }) => {
                $router.go(-1);
            },
            renderCondition: () => true,
        },
    ]);

    crud.view.addPageControls([
        {
            label: 'tasks.relations.title',
            icon: 'icon-corner-down-right',
            onClick: ({ $router }) => {
                $router.push({ name: relationsRouteName });
            },
            renderCondition: ({ $can, values }) => {
                if (!('can' in values)) return false;

                return $can('update', 'task', values);
            },
        },
        {
            label: 'control.edit',
            renderType: 'primary',
            icon: 'icon-edit',
            onClick: ({ $router }) => {
                $router.push({ name: crudEditRoute });
            },
            renderCondition: ({ $can, values }) => {
                if (!('can' in values)) return false;

                return $can('update', 'task', values);
            },
        },
    ]);

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    context.addNavbarEntry({
        label: 'navigation.tasks',
        to: {
            name: 'Tasks.crud.tasks',
        },
    });

    context.addRoute(crud.getRouterConfig());
    context.addRoute(grid.getRouterConfig());
    return context;
}
