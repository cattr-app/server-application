import Vue from 'vue';
import cloneDeep from 'lodash/cloneDeep';
import { store } from '@/store';
import i18n from '@/i18n';
import { formatDate } from '@/utils/time';
import InvitationService from '../services/invitation.service';
import InvitationForm from '../components/InvitationForm';
import Invitations from '../views/Invitations';
import { hasRole } from '@/utils/user';

export function fieldsToFillProvider() {
    return [
        {
            label: 'field.users',
            key: 'users',
            required: true,
            render: (h, props) => {
                return h(InvitationForm, {
                    props: {
                        value: props.currentValue,
                    },
                    on: {
                        input(value) {
                            props.inputHandler(value);
                        },
                    },
                });
            },
        },
    ];
}

export const config = { fieldsToFillProvider };

export default (context, router) => {
    const invitationsContext = cloneDeep(context);
    invitationsContext.routerPrefix = 'company/invitations';

    const crud = invitationsContext.createCrud('invitations.crud-title', 'invitations', InvitationService);
    const crudNewRoute = crud.new.getNewRouteName();

    const navigation = { new: crudNewRoute };

    crud.new.addToMetaProperties('permissions', 'invitations/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties('navigation', navigation, crud.new.getRouterConfig());
    crud.new.addToMetaProperties('afterSubmitCallback', () => router.go(-1), crud.new.getRouterConfig());

    const grid = invitationsContext.createGrid('invitations.grid-title', 'invitations', InvitationService);
    grid.addToMetaProperties('navigation', navigation, grid.getRouterConfig());
    grid.addToMetaProperties('permissions', () => hasRole(store.getters['user/user'], 'admin'), grid.getRouterConfig());

    const fieldsToFill = config.fieldsToFillProvider();

    crud.new.addField(fieldsToFill);

    grid.addColumn([
        {
            title: 'field.email',
            key: 'email',
        },
        {
            title: 'field.expires_at',
            key: 'expires_at',
            render(h, { item }) {
                const expiresAt = formatDate(item.expires_at);
                return h('span', [expiresAt]);
            },
        },
    ]);

    grid.addFilter([
        {
            filterName: 'filter.fields.email',
            referenceKey: 'email',
        },
    ]);

    grid.addAction([
        {
            title: 'invite.resend',
            actionType: 'primary',
            icon: 'icon-refresh-ccw',
            onClick: async (router, { item }, context) => {
                const invitationService = new InvitationService();
                try {
                    await invitationService.resend(item.id);
                    context.fetchData();
                    context.$Message.success(i18n.t('message.success'));
                } catch (e) {
                    //
                }
            },
        },
        {
            title: 'control.delete',
            actionType: 'error',
            icon: 'icon-trash-2',
            onClick: (router, { item }, context) => {
                context.onDelete(item);
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
        accessCheck: async () => Vue.prototype.$can('viewAny', 'invitation'),
        scope: 'company',
        order: 20,
        component: Invitations,
        route: {
            name: 'Invitations.crud.invitations',
            path: '/company/invitations',
            meta: {
                label: 'navigation.invitations',
                service: new InvitationService(),
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
