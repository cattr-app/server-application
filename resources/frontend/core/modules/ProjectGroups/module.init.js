import ProjectGroupsService from '@/services/resource/project-groups.service';
import GroupSelect from '@/components/GroupSelect';
import i18n from '@/i18n';
import Vue from 'vue';

export const ModuleConfig = {
    routerPrefix: 'project-groups',
    loadOrder: 20,
    moduleName: 'ProjectGroups',
};

export function init(context) {
    const crud = context.createCrud('groups.crud-title', 'groups', ProjectGroupsService);

    const crudEditRoute = crud.edit.getEditRouteName();
    const crudNewRoute = crud.new.getNewRouteName();

    const navigation = { edit: crudEditRoute, new: crudNewRoute };
    crud.new.addToMetaProperties('permissions', 'groups/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties('navigation', navigation, crud.new.getRouterConfig());

    crud.edit.addToMetaProperties('permissions', 'groups/edit', crud.edit.getRouterConfig());

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
            label: 'field.parent_group',
            key: 'parent_id',
            render: (h, data) => {
                return h(GroupSelect, {
                    props: { value: data.values.group_parent },
                    on: {
                        input(value) {
                            Vue.set(data.values, 'group_parent', value);
                            data.values.parent_id = value?.id ?? null;
                        },
                    },
                });
            },
            required: false,
        },
    ];

    crud.new.addField(fieldsToFill);
    crud.edit.addField(fieldsToFill);

    context.addRoute(crud.getRouterConfig());

    crud.edit.addPageControlsToBottom([
        {
            title: 'control.delete',
            type: 'error',
            icon: 'icon-trash-2',
            onClick: async ({ service, $router }, item) => {
                const isConfirm = await Vue.prototype.$CustomModal({
                    title: i18n.t('notification.record.delete.confirmation.title'),
                    content: i18n.t('notification.record.delete.confirmation.message'),
                    okText: i18n.t('control.delete'),
                    cancelText: i18n.t('control.cancel'),
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

                await service.deleteItem(item);
                Vue.prototype.$Notify({
                    type: 'success',
                    title: i18n.t('notification.record.delete.success.title'),
                    message: i18n.t('notification.record.delete.success.message'),
                });

                $router.push({ name: context.getModuleRouteName() });
            },
            renderCondition: ({ $can }) => {
                return $can('delete', 'projectGroup');
            },
        },
    ]);

    context.addRoute({
        path: `/${context.routerPrefix}`,
        name: context.getModuleRouteName(),
        component: () => import(/* webpackChunkName: "project-groups" */ './views/ProjectGroups.vue'),
        meta: {
            auth: true,
        },
    });

    context.addNavbarEntryDropDown({
        label: 'navigation.project-groups',
        section: 'navigation.dropdown.projects',
        to: {
            name: context.getModuleRouteName(),
        },
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
