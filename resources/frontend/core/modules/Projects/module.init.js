import moment from 'moment-timezone';
import ProjectService from '@/services/resource/project.service';
import i18n from '@/i18n';
import { formatDurationString } from '@/utils/time';
import { ModuleLoaderInterceptor } from '@/moduleLoader';
import PrioritySelect from '@/components/PrioritySelect';
import TeamAvatars from '@/components/TeamAvatars';
import Statuses from './components/Statuses';

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

    const crud = context.createCrud('projects.crud-title', 'projects', ProjectService);

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
        with: ['users', 'defaultPriority', 'statuses', 'can'],
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
            key: 'total_spent_time',
            label: 'field.total_spent',
            render: (h, props) => h('span', formatDurationString(props.currentValue)),
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
            key: 'workers',
            label: 'field.users',
            render: (h, props) => {
                const data = [];
                Object.keys(props.currentValue).forEach(k => {
                    props.currentValue[k].time = formatDurationString(+props.currentValue[k].duration);
                    data.push(props.currentValue[k]);
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
                                                    name: routes.usersView,
                                                    params: { id: item.user_id },
                                                },
                                            },
                                        },
                                        item.full_name,
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
                                                    name: routes.tasksView,
                                                    params: { id: item.task_id },
                                                },
                                            },
                                        },
                                        item.task_name,
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
                        data,
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
            label: 'field.important',
            tooltipValue: 'tooltip.task_important',
            key: 'important',
            type: 'checkbox',
            default: 0,
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
        },
        {
            title: 'field.members',
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

    grid.addFilter([
        {
            referenceKey: 'name',
            filterName: 'filter.fields.project_name',
        },
    ]);

    const tasksRouteName = context.getModuleRouteName() + '.tasks';
    const assignRouteName = context.getModuleRouteName() + '.members';
    context.addRoute([
        // {
        //     path: `/${context.routerPrefix}/:id/tasks/kanban`,
        //     name: tasksRouteName,
        //     component: () => import('./views/Tasks.vue'),
        //     meta: {
        //         auth: true,
        //     },
        // },
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

    context.addNavbarEntry({
        label: 'navigation.projects',
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
