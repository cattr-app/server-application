import cloneDeep from 'lodash/cloneDeep';
import i18n from '@/i18n';
import { store } from '@/store';
import StatusService from '../services/statuse.service';
import Statuses from '../views/Statuses';
import ColorInput from '@/components/ColorInput';
import { hasRole } from '@/utils/user';
import Vue from 'vue';
import { throttle } from 'lodash';

export default (context, router) => {
    const statusesContext = cloneDeep(context);
    statusesContext.routerPrefix = 'company/statuses';

    const crud = statusesContext.createCrud('statuses.crud-title', 'statuses', StatusService);
    const crudEditRoute = crud.edit.getEditRouteName();
    const crudNewRoute = crud.new.getNewRouteName();

    const navigation = { edit: crudEditRoute, new: crudNewRoute };
    const statusOrder = throttle(async (data, direction) => {
        const { gridView } = data;
        const { tableData } = gridView;

        const service = new StatusService();
        const index = tableData.findIndex(item => item.id === data.item.id);

        const item = tableData[index];
        const targetIndex = direction === 'up' ? index - 1 : index + 1;
        const targetItem = tableData[targetIndex];

        await service.save({ ...targetItem, order: item.order });

        Vue.set(tableData, index, { ...targetItem, order: item.order });
        Vue.set(tableData, targetIndex, { ...item, order: targetItem.order });
    }, 1000);
    crud.new.addToMetaProperties('permissions', 'statuses/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties('navigation', navigation, crud.new.getRouterConfig());
    crud.new.addToMetaProperties('afterSubmitCallback', () => router.go(-1), crud.new.getRouterConfig());

    crud.edit.addToMetaProperties('permissions', 'statuses/edit', crud.edit.getRouterConfig());

    const grid = statusesContext.createGrid('statuses.grid-title', 'statuses', StatusService, {
        orderBy: ['order', 'asc'],
    });

    grid.addToMetaProperties('navigation', navigation, grid.getRouterConfig());
    grid.addToMetaProperties('permissions', () => hasRole(store.getters['user/user'], 'admin'), grid.getRouterConfig());

    const fieldsToFill = [
        {
            key: 'id',
            displayable: false,
        },
        {
            label: 'field.name',
            key: 'name',
            type: 'input',
            required: true,
            placeholder: 'field.name',
        },
        {
            label: 'field.close_task',
            key: 'active',
            required: false,
            initialValue: true,
            render: (h, data) => {
                return h('at-checkbox', {
                    props: {
                        checked: typeof data.currentValue === 'boolean' ? !data.currentValue : false,
                    },
                    on: {
                        'on-change'(value) {
                            data.inputHandler(!value);
                        },
                    },
                });
            },
        },
        {
            label: 'field.color',
            key: 'color',
            required: false,
            render: (h, data) => {
                return h(ColorInput, {
                    props: {
                        value: typeof data.currentValue === 'string' ? data.currentValue : 'transparent',
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

    crud.edit.addField(fieldsToFill);
    crud.new.addField(fieldsToFill);
    grid.addColumn([
        {
            title: 'field.name',
            key: 'name',
        },
        {
            title: 'field.order',
            key: 'order',
            render(h, data) {
                const index = data.gridView.tableData.findIndex(item => item.id === data.item.id);
                const result = [];
                result.push(
                    h(
                        'at-button',
                        {
                            style: {
                                marginRight: '8px',
                            },
                            on:
                                index > 0
                                    ? {
                                          click: function () {
                                              statusOrder(data, 'up');
                                          },
                                      }
                                    : {},
                            props:
                                index > 0
                                    ? {}
                                    : {
                                          disabled: true,
                                      },
                        },
                        [h('i', { class: 'icon icon-chevrons-up' })],
                    ),
                );
                result.push(
                    h(
                        'at-button',
                        {
                            style: {
                                marginRight: '8px',
                            },
                            on:
                                index < data.gridView.tableData.length - 1
                                    ? {
                                          click: function () {
                                              statusOrder(data, 'down');
                                          },
                                      }
                                    : {},
                            props:
                                index < data.gridView.tableData.length - 1
                                    ? {}
                                    : {
                                          disabled: true,
                                      },
                        },
                        [h('i', { class: 'icon icon-chevrons-down' })],
                    ),
                );
                return result;
            },
        },

        {
            title: 'field.close_task',
            key: 'active',
            render(h, { item }) {
                return h('span', [i18n.t('control.' + (!item.active ? 'yes' : 'no'))]);
            },
        },
        {
            title: 'field.color',
            key: 'color',
            render(h, { item }) {
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
                                background: item.color,
                                borderRadius: '4px',
                                width: '16px',
                                height: '16px',
                                margin: '0 4px 0 0',
                            },
                        }),
                        h('span', {}, [item.color]),
                    ],
                );
            },
        },
    ]);

    grid.addAction([
        {
            title: 'control.edit',
            icon: 'icon-edit',
            onClick: (router, { item }, context) => {
                context.onEdit(item);
            },
            renderCondition: ({ $can }, item) => {
                return $can('update', 'status', item);
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
                return $can('delete', 'status', item);
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
        },
    ]);

    return {
        accessCheck: async () => hasRole(store.getters['user/user'], 'admin'),
        scope: 'company',
        order: 20,
        component: Statuses,
        route: {
            name: 'Statuses.crud.statuses',
            path: '/company/statuses',
            meta: {
                label: 'navigation.statuses',
                service: new StatusService(),
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
