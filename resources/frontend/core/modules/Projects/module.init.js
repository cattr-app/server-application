import moment from 'moment-timezone';
import ProjectService from '@/services/resource/project.service';
import i18n from '@/i18n';
import { formatDurationString } from '@/utils/time';
import { ModuleLoaderInterceptor } from '@/moduleLoader';
import PrioritySelect from '@/components/PrioritySelect';
import ScreenshotsStateSelect from '@/components/ScreenshotsStateSelect';
import TeamAvatars from '@/components/TeamAvatars';
import { store } from '@/store';
import Statuses from './components/Statuses';
import Phases from './components/Phases.vue';
import Vue from 'vue';
import GroupSelect from '@/components/GroupSelect';

export const ModuleConfig = {
    routerPrefix: 'projects',
    loadOrder: 20,
    moduleName: 'Projects',
};

function formatDateTime(value, timezone) {
    const date = moment.tz(value, timezone || moment.tz.guess());
    return date.locale(i18n.locale).format('MMMM D, YYYY — HH:mm:ss ([GMT]Z)');
}

export function init(context) {
    let routes = {};

    ModuleLoaderInterceptor.on('AmazingCat_CoreModule', m => {
        m.routes.forEach(route => {
            if (route.name.search('users.view') > 0) {
                routes.usersView = route.name;
            }
        });
    });

    ModuleLoaderInterceptor.on('AmazingCat_TasksModule', m => {
        m.routes.forEach(route => {
            if (route.name.search('view') > 0) {
                routes.tasksView = route.name;
            }
        });
    });

    const crud = context.createCrud('projects.crud-title', 'projects', ProjectService, {
        with: [
            'defaultPriority',
            'tasks',
            'workers',
            'workers.task:id,task_name',
            'workers.user:id,full_name',
            'group:id,name',
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

    crud.view.addToMetaProperties('titleCallback', ({ values }) => values.name, crud.view.getRouterConfig());
    crud.view.addToMetaProperties('navigation', navigation, crud.view.getRouterConfig());

    crud.new.addToMetaProperties('permissions', 'projects/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties('navigation', navigation, crud.new.getRouterConfig());

    crud.edit.addToMetaProperties('permissions', 'projects/edit', crud.edit.getRouterConfig());

    const grid = context.createGrid('projects.grid-title', 'projects', ProjectService, {
        with: ['users', 'defaultPriority', 'statuses', 'can', 'group:id,name'],
        withCount: ['tasks'],
    });
    grid.addToMetaProperties('navigation', navigation, grid.getRouterConfig());

    const fieldsToShow = [
        {
            label: 'field.name',
            key: 'name',
        },
        {
            label: 'field.created_at',
            key: 'created_at',
            render: (h, { currentValue, companyData }) => {
                return h('span', formatDateTime(currentValue, companyData.timezone));
            },
        },
        {
            label: 'field.updated_at',
            key: 'updated_at',
            render: (h, { currentValue, companyData }) => {
                return h('span', formatDateTime(currentValue, companyData.timezone));
            },
        },
        {
            label: 'field.description',
            key: 'description',
        },
        {
            label: 'field.group',
            key: 'group',
            render: (h, props) =>
                h('span', props.currentValue !== null ? props.currentValue.name : i18n.t('field.no_group_selected')),
        },
        {
            key: 'total_spent_time',
            label: 'field.total_spent',
            render: (h, props) => {
                const timeWithOffset = +props.values.total_spent_time + +props.values.total_offset;
                return h('span', formatDurationString(timeWithOffset > 0 ? timeWithOffset : 0));
            },
        },
        {
            label: 'field.phases',
            key: 'phases',
            render: (h, data) => {
                return h(Phases, {
                    props: {
                        phases: Array.isArray(data.currentValue) ? data.currentValue : [],
                        showControls: false,
                    },
                });
            },
        },
        {
            key: 'default_priority',
            label: 'field.default_priority',
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
            label: 'field.screenshots_state',
            key: 'screenshots_state',
            render: (h, { currentValue }) => {
                return h('span', currentValue ? i18n.t('control.yes') : i18n.t('control.no'));
            },
        },
        {
            key: 'workers',
            label: 'field.users',
            render: (h, props) => {
                const tableData = [];
                const globalTimeWithOffset = +props.values.total_spent_time + +props.values.total_offset;
                Object.keys(props.currentValue).forEach(k => {
                    const timeWithOffset = +props.currentValue[k].duration + +props.currentValue[k].offset;
                    props.currentValue[k].time = formatDurationString(timeWithOffset);
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
                                        item.user.full_name,
                                    );
                                },
                            },
                            {
                                title: i18n.t('field.task'),
                                render: (h, { item }) => {
                                    return h(
                                        'router-link',
                                        {
                                            props: {
                                                to: {
                                                    name: 'Tasks.crud.tasks.view',
                                                    params: { id: item.task_id },
                                                },
                                            },
                                        },
                                        item.task.task_name,
                                    );
                                },
                            },
                            {
                                key: 'time',
                                title: i18n.t('field.time'),
                                render(h, { item }) {
                                    return h('div', {
                                        domProps: {
                                            textContent: !item
                                                ? `0${i18n.t('time.h')} 0${i18n.t('time.m')}`
                                                : item.time,
                                        },
                                        styles: {
                                            'white-space': 'nowrap',
                                        },
                                    });
                                },
                            },
                        ],
                        data: tableData,
                        pagination: true,
                        'page-size': 100,
                    },
                });
            },
        },
    ];

    const fieldsToFill = [
        {
            key: 'id',
            displayable: false,
        },
        {
            label: 'field.name',
            key: 'name',
            type: 'text',
            placeholder: 'field.name',
            required: true,
        },
        {
            label: 'field.description',
            key: 'description',
            type: 'textarea',
            required: true,
            placeholder: 'field.description',
        },
        {
            label: 'field.group',
            key: 'group',
            render(h, data) {
                return h(GroupSelect, {
                    props: { value: data.values.group },
                    on: {
                        input(value) {
                            Vue.set(data.values, 'group', value);
                        },
                    },
                });
            },
            required: false,
        },
        {
            label: 'field.important',
            tooltipValue: 'tooltip.task_important',
            key: 'important',
            type: 'checkbox',
            default: 0,
        },
        {
            label: 'field.phases',
            key: 'phases',
            render: (h, data) => {
                return h(Phases, {
                    props: {
                        phases: Array.isArray(data.currentValue) ? data.currentValue : [],
                    },
                    on: {
                        change(value) {
                            data.inputHandler(value);
                        },
                    },
                });
            },
            required: false,
        },
        {
            label: 'field.default_priority',
            key: 'default_priority_id',
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
            required: false,
        },
        {
            label: 'field.screenshots_state',
            key: 'screenshots_state',
            default: 1,
            render: (h, props) => {
                return h(ScreenshotsStateSelect, {
                    props: {
                        value: props.values.screenshots_state,
                        isDisabled: store.getters['screenshots/isProjectStateLocked'],
                        hideIndexes: [0],
                    },
                    on: {
                        input(value) {
                            props.inputHandler(value);
                        },
                    },
                });
            },
        },
        {
            label: 'field.statuses',
            key: 'statuses',
            render: (h, data) => {
                const value = Array.isArray(data.currentValue) ? data.currentValue : [];

                return h(Statuses, {
                    props: {
                        value,
                    },
                    on: {
                        change(value) {
                            data.inputHandler(value);
                        },
                    },
                });
            },
        },
    ];

    crud.view.addField(fieldsToShow);
    crud.new.addField(fieldsToFill);
    crud.edit.addField(fieldsToFill);

    grid.addColumn([
        {
            title: 'field.project',
            key: 'name',
            render: (h, { item }) => {
                return h(
                    'span',
                    {
                        class: ['projects-grid__project'],
                        attrs: { title: item.name },
                    },
                    item.name,
                );
            },
        },
        {
            title: 'field.group',
            key: 'group',
            render: (h, data) => {
                if (!data.item.can.update) {
                    return h('span', data.item.group?.name ?? '');
                }

                return h(GroupSelect, {
                    props: { value: data.item.group },
                    on: {
                        input(value) {
                            data.item.group = value;

                            new ProjectService().save({
                                id: data.item.id,
                                group: data.item.group?.id ?? null,
                            });
                        },
                    },
                });
            },
        },
        {
            title: 'field.members',
            key: 'users',
            hideForMobile: true,
            render: (h, { item }) => {
                return h(TeamAvatars, {
                    props: {
                        users: item.users || [],
                    },
                });
            },
        },
        {
            title: 'field.amount_of_tasks',
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
    ]);

    const websocketLeaveChannel = id => Vue.prototype.$echo.leave(`projects.${id}`);
    const websocketEnterChannel = (id, handlers) => {
        const channel = Vue.prototype.$echo.private(`projects.${id}`);
        for (const action in handlers) {
            channel.listen(`.projects.${action}`, handlers[action]);
        }

        channel.listen('.projects.edit', function () {
            store.dispatch('projectGroups/resetGroups');
        });
    };

    grid.addToMetaProperties('gridData.websocketEnterChannel', websocketEnterChannel, grid.getRouterConfig());
    grid.addToMetaProperties('gridData.websocketLeaveChannel', websocketLeaveChannel, grid.getRouterConfig());

    crud.view.addToMetaProperties('pageData.websocketEnterChannel', websocketEnterChannel, crud.view.getRouterConfig());
    crud.view.addToMetaProperties('pageData.websocketLeaveChannel', websocketLeaveChannel, crud.view.getRouterConfig());

    grid.addFilter([
        {
            referenceKey: 'name',
            filterName: 'filter.fields.project_name',
        },
    ]);

    const tasksRouteName = context.getModuleRouteName() + '.tasks';
    const assignRouteName = context.getModuleRouteName() + '.members';
    context.addRoute([
        {
            path: `/${context.routerPrefix}/:id/tasks/kanban`,
            name: tasksRouteName,
            component: () => import('./views/Tasks.vue'),
            meta: {
                auth: true,
            },
        },
        {
            path: `/${context.routerPrefix}/:id/members`,
            name: assignRouteName,
            component: () => import('./views/ProjectMembers.vue'),
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
            renderCondition({ $store }) {
                // User always can view assigned projects
                return true;
            },
        },
        {
            title: 'projects.gantt',
            icon: 'icon-crop',
            onClick: (router, { item }, context) => {
                router.push({ name: 'Gantt.index', params: { id: item.id } });
            },
            renderCondition({ $store }) {
                // User always can view assigned projects
                return true;
            },
        },
        {
            title: 'projects.tasks',
            icon: 'icon-list',
            onClick: (router, { item }) => {
                router.push({ name: tasksRouteName, params: { id: item.id } });
            },
            renderCondition({ $can }, item) {
                // User always can view project's tasks
                return false;
            },
        },
        {
            title: 'projects.members',
            icon: 'icon-users',
            onClick: (router, { item }) => {
                router.push({ name: assignRouteName, params: { id: item.id } });
            },
            renderCondition({ $can }, item) {
                return $can('updateMembers', 'project', item);
            },
        },
        {
            title: 'projects.kanban',
            icon: 'icon-bar-chart-2',
            onClick: (router, { item }) => {
                router.push({ name: tasksRouteName, params: { id: item.id } });
            },
            renderCondition({ $can }, item) {
                return true;
            },
        },
        {
            title: 'control.edit',
            icon: 'icon-edit',
            onClick: (router, { item }, context) => {
                context.onEdit(item);
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
                context.onDelete(item);
            },
            renderCondition: ({ $can }, item) => {
                return $can('delete', 'project', item);
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
                return $can('create', 'project');
            },
        },
    ]);

    context.addRoute(crud.getRouterConfig());
    context.addRoute(grid.getRouterConfig());

    context.addNavbarEntryDropDown({
        label: 'navigation.projects',
        section: 'navigation.dropdown.projects',
        to: {
            name: 'Projects.crud.projects',
        },
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
