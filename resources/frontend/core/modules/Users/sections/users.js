import cloneDeep from 'lodash/cloneDeep';
import TimezonePicker from '@/components/TimezonePicker';
import CoreUsersService from '@/services/resource/user.service';
import RoleSelect from '@/components/RoleSelect';
import Users from '../views/Users';
import UsersService from '../services/user.service';
import { store } from '@/store';
import LanguageSelector from '@/components/LanguageSelector';
import i18n from '@/i18n';
import Vue from 'vue';

export function fieldsToFillProvider() {
    return [
        {
            key: 'id',
            displayable: () => false,
        },
        {
            label: 'field.full_name',
            key: 'full_name',
            type: 'input',
            placeholder: 'field.full_name',
            required: true,
        },
        {
            label: 'field.email',
            key: 'email',
            type: 'input',
            frontendType: 'email',
            required: true,
            placeholder: 'field.email',
        },
        {
            label: 'field.active',
            key: 'active',
            required: true,
            default: true,
            type: 'checkbox',
            render(h, props) {
                if (typeof props.currentValue === 'object') {
                    return;
                }

                props.currentValue = Boolean(props.currentValue);
                props.inputHandler(props.currentValue);

                let isDisable = false;
                const selfId = store.getters['user/user'].id;
                const userId = props.values.id;
                if (selfId === userId) {
                    isDisable = !isDisable;
                }

                return h('at-checkbox', {
                    props: {
                        checked: props.currentValue,
                        disabled: isDisable,
                    },
                    on: {
                        'on-change'(value) {
                            props.inputHandler(value);
                        },
                    },
                });
            },
        },
        {
            label: 'field.screenshots_active',
            key: 'screenshots_active',
            type: 'checkbox',
            default: 1,
        },
        {
            label: 'field.password',
            key: 'password',
            type: 'input',
            frontendType: 'password',
            placeholder: 'field.password',
        },
        {
            label: 'field.send_invite',
            key: 'send_invite',
            type: 'checkbox',
            tooltipValue: 'tooltip.user_send_invite',
            default: 1,
            displayable: context => {
                // If we edit an existing user
                // then we don't display this field
                return !context.values.id;
            },
        },
        {
            label: 'field.manual_time',
            key: 'manual_time',
            type: 'checkbox',
            tooltipValue: 'tooltip.user_manual_time',
            default: 1,
        },
        {
            label: 'field.user_language',
            key: 'user_language',
            render: (h, props) => {
                if (typeof props.currentValue === 'object' || props.currentValue === '') {
                    const defaultLang = props.companyData.language || '';
                    props.currentValue = defaultLang;
                    props.inputHandler(defaultLang);
                }
                return h(LanguageSelector, {
                    props: {
                        value: props.currentValue,
                    },
                    on: {
                        setLanguage(lang) {
                            props.inputHandler(lang);
                        },
                    },
                });
            },
        },
        {
            label: 'field.screenshots_interval',
            key: 'screenshots_interval',
            type: 'input',
            placeholder: 'field.screenshots_interval',
            tooltipValue: 'tooltip.user_interval_screenshot',
            default: 10,
        },
        {
            label: 'field.computer_time_popup',
            key: 'computer_time_popup',
            type: 'input',
            tooltipValue: 'tooltip.user_computer_time_popup',
            placeholder: 'field.computer_time_popup',
            default: 3,
        },
        {
            label: 'field.timezone',
            key: 'timezone',
            render: (h, props) => {
                if (typeof props.currentValue === 'object' && props.companyData.timezone) {
                    props.currentValue = props.companyData.timezone;
                    props.inputHandler(props.companyData.timezone);
                } else if (typeof props.currentValue === 'object' || !props.currentValue) {
                    props.currentValue = '';
                }

                return h(TimezonePicker, {
                    props: {
                        value: props.currentValue,
                    },
                    on: {
                        onTimezoneChange(ev) {
                            props.inputHandler(ev);
                        },
                    },
                });
            },
        },
        {
            label: 'field.default_role',
            key: 'role_id',
            render(h, props) {
                if (typeof props.currentValue === 'object') {
                    const default_role = 2;
                    props.currentValue = default_role;
                    props.inputHandler(default_role);
                }

                return h(RoleSelect, {
                    props: {
                        value: props.currentValue,
                    },
                    on: {
                        updateProps(ruleId) {
                            props.inputHandler(ruleId);
                        },
                    },
                });
            },
        },
        {
            label: 'field.type',
            key: 'type',
            type: 'select',
            options: [
                {
                    value: 'employee',
                    label: 'field.types.employee',
                },
                {
                    value: 'client',
                    label: 'field.types.client',
                },
            ],
            default: 'employee',
        },
        {
            label: 'field.web_and_app_monitoring',
            key: 'web_and_app_monitoring',
            type: 'checkbox',
            default: 1,
        },
    ];
}

export const config = { fieldsToFillProvider };

export default (context, router) => {
    const usersContext = cloneDeep(context);
    usersContext.routerPrefix = 'company/users';

    const crud = usersContext.createCrud('users.crud-title', 'users', CoreUsersService);
    const crudViewRoute = crud.view.getViewRouteName();
    const crudEditRoute = crud.edit.getEditRouteName();
    const crudNewRoute = crud.new.getNewRouteName();

    const navigation = { view: crudViewRoute, edit: crudEditRoute, new: crudNewRoute };

    crud.view.addToMetaProperties('permissions', 'users/show', crud.view.getRouterConfig());
    crud.view.addToMetaProperties('navigation', navigation, crud.view.getRouterConfig());

    crud.new.addToMetaProperties('permissions', 'users/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties('navigation', navigation, crud.new.getRouterConfig());

    crud.edit.addToMetaProperties('permissions', 'users/edit', crud.edit.getRouterConfig());

    const grid = usersContext.createGrid('users.grid-title', 'users', CoreUsersService);
    grid.addToMetaProperties('navigation', navigation, grid.getRouterConfig());
    grid.addToMetaProperties('style', 'compact', grid.getRouterConfig());
    grid.addToMetaProperties('sortable', true, grid.getRouterConfig());

    const fieldsToShow = [
        {
            label: 'ID',
            key: 'id',
        },
        {
            label: 'field.full_name',
            key: 'full_name',
        },
        {
            label: 'field.email',
            key: 'email',
        },
        {
            label: 'field.active',
            key: 'active',
            render: (h, { currentValue }) => {
                return h('span', currentValue ? i18n.t('control.yes') : i18n.t('control.no'));
            },
        },
        {
            label: 'field.screenshots_active',
            key: 'screenshots_active',
            render: (h, { currentValue }) => {
                return h('span', currentValue ? i18n.t('control.yes') : i18n.t('control.no'));
            },
        },
        {
            label: 'field.manual_time',
            key: 'manual_time',
            tooltipValue: 'tooltip.user_manual_time',
            render: (h, { currentValue }) => {
                return h('span', currentValue ? i18n.t('control.yes') : i18n.t('control.no'));
            },
        },
        {
            label: 'field.user_language',
            key: 'user_language',
            render: (h, { currentValue }) => {
                const value = currentValue ? i18n.t(`languages.${currentValue}`) : i18n.t('languages.default');
                return h('span', value);
            },
        },
        {
            label: 'field.screenshots_interval',
            key: 'screenshots_interval',
            render: (h, { currentValue }) => {
                return h('span', i18n.t('field.minutes', { value: currentValue }));
            },
        },
        {
            label: 'field.computer_time_popup',
            key: 'computer_time_popup',
            render: (h, { currentValue }) => {
                return h('span', i18n.t('field.minutes', { value: currentValue }));
            },
        },
        {
            label: 'field.timezone',
            key: 'timezone',
        },
        {
            label: 'field.role',
            key: 'role_id',
            render: (h, { currentValue }) => {
                const roleName = Object.keys(store.getters['roles/roles']).find(
                    el => store.getters['roles/roles'][el] === currentValue,
                );
                return h('span', i18n.t(`field.roles.${roleName}.name`));
            },
        },
        {
            label: 'field.type',
            key: 'type',
            render: (h, { currentValue }) => {
                return h('span', i18n.t(`field.types.${currentValue}`));
            },
        },
    ];

    const fieldsToFill = config.fieldsToFillProvider();

    crud.view.addField(fieldsToShow);
    crud.edit.addField(fieldsToFill);
    crud.new.addField(fieldsToFill);

    grid.addFilter([
        {
            filterName: 'filter.fields.full_name',
            referenceKey: 'full_name',
        },
        {
            filterName: 'filter.fields.email',
            referenceKey: 'email',
        },
    ]);

    grid.addFilterField([
        {
            key: 'active',
            label: 'field.statuses',
            placeholder: 'users.statuses.any',
            saveToQuery: true,
            fieldOptions: {
                type: 'select',
                options: [
                    {
                        value: '',
                        label: 'users.statuses.any',
                    },
                    {
                        value: '0',
                        label: 'users.statuses.disabled',
                    },
                    {
                        value: '1',
                        label: 'users.statuses.active',
                    },
                ],
            },
        },
        {
            key: 'role_id',
            label: 'field.role',
            placeholder: 'field.roles.any',
            saveToQuery: true,
            fieldOptions: {
                type: 'select',
                options: [
                    {
                        value: '',
                        label: 'field.roles.any',
                    },
                    {
                        value: '1',
                        label: 'field.roles.manager.name',
                    },
                    {
                        value: '2',
                        label: 'field.roles.user.name',
                    },
                    {
                        value: '3',
                        label: 'field.roles.auditor.name',
                    },
                ],
            },
        },
        {
            key: 'type',
            label: 'field.type',
            placeholder: 'field.types.any',
            saveToQuery: true,
            fieldOptions: {
                type: 'select',
                options: [
                    {
                        value: '',
                        label: 'field.types.any',
                    },
                    {
                        value: 'employee',
                        label: 'field.types.employee',
                    },
                    {
                        value: 'client',
                        label: 'field.types.client',
                    },
                ],
            },
        },
    ]);

    grid.addColumn([
        {
            title: 'field.full_name',
            key: 'full_name',
        },
        {
            title: 'field.status',
            key: 'active',
            render(h, { item }) {
                const status = i18n.t('users.statuses.' + (item.active ? 'active' : 'disabled'));

                return h('span', [status]);
            },
        },
        {
            title: 'field.role',
            key: 'role_id',
            render(h, { item }) {
                const roleName = Object.keys(store.getters['roles/roles']).find(
                    el => store.getters['roles/roles'][el] === item.role_id,
                );
                return h('span', i18n.t(`field.roles.${roleName}.name`));
            },
        },
        {
            title: 'field.email',
            key: 'email',
        },
    ]);

    grid.addAction([
        {
            //title: 'control.view',
            icon: 'icon-eye',
            onClick: (router, { item }, context) => {
                context.onView(item);
            },
            renderCondition: ({ $can }) => true,
        },
        {
            //title: 'control.edit',
            icon: 'icon-edit',
            onClick: (router, { item }, context) => {
                context.onEdit(item);
            },
            renderCondition({ $can }, item) {
                return $can('update', 'user', item);
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
            renderCondition({ $can }) {
                return $can('create', 'user');
            },
        },
    ]);

    crud.edit.addPageControls([
        {
            label: 'invite.resend',
            renderType: 'primary',
            icon: 'icon-mail',
            onClick: async ({ $Message, values }) => {
                const service = new UsersService();
                await service.sendInvite(values.id);
                $Message.success(i18n.t('message.success'));
            },
            renderCondition({ $can, values }, item) {
                return $can('update', 'user', item) && values.invitation_sent;
            },
        },
    ]);

    return {
        // Check if this section can be rendered and accessed, this param IS OPTIONAL (true by default)
        // NOTICE: this route will not be added to VueRouter AT ALL if this check fails
        // MUST be a function that returns a boolean
        accessCheck: async () => Vue.prototype.$can('viewAny', 'user'),

        scope: 'company',
        order: 10,
        component: Users,
        route: {
            // After processing this route will be named as 'settings.exampleSection'
            name: 'Users.crud.users',

            // After processing this route can be accessed via URL 'settings/example'
            path: '/company/users',

            meta: {
                // After render, this section will be labeled as 'Example Section'
                label: 'navigation.users',

                // Service class to gather the data from API, should be an instance of Resource class
                service: new UsersService(),
            },

            children: [
                {
                    ...grid.getRouterConfig(),
                    path: '',
                },
                ...crud.getRouterConfig(),
            ],
        },
    };
};
