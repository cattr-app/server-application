import ProjectGroupsService from '@/services/resource/project-groups.service';
import Groups from '../Projects/components/Groups';
import moment from 'moment-timezone';
import i18n from '@/i18n';


export const ModuleConfig = {
    routerPrefix: 'groups',
    moduleName: 'Groups',
}

function formatDateTime(value, timezone) {
    const date = moment.tz(value, timezone || moment.tz.guess());
    return date.locale(i18n.locale).format('MMMM D, YYYY — HH:mm:ss ([GMT]Z)');
}

function setParentGroup(data) {
    if (
        data.values._currentGroup == null
        && (typeof data.currentValue?.id === 'number' 
        || typeof data.currentValue?.id === 'string')
    ) {
        data.setValue('_currentGroup', data.currentValue);
    }

    let currentGroup = data.values._currentGroup || '';

    let value = '';

    if (
        typeof data.currentValue === 'number' 
        || typeof data.currentValue === 'string'
    ) {
        value = data.currentValue;
        data.inputHandler(value);
    } else if (
        typeof data.currentValue?.id === 'number' 
        || typeof data.currentValue?.id === 'string'
    ) {
        value = data.currentValue.id;
        data.inputHandler(value);
    }

    return [currentGroup, value];
}

export function init(context, r) {
    const crud = context.createCrud('groups.crud-title', 'groups', ProjectGroupsService);

    const crudViewRoute = crud.view.getViewRouteName();
    const crudEditRoute = crud.edit.getEditRouteName();
    const crudNewRoute = crud.new.getNewRouteName();

    const navigation = { view: crudViewRoute, edit: crudEditRoute, new: crudNewRoute };

    crud.view.addToMetaProperties('titleCallback', ({ values }) => values.name, crud.view.getRouterConfig());
    crud.view.addToMetaProperties('navigation', navigation, crud.view.getRouterConfig());
    crud.view.addToMetaProperties('permissions', 'groups', crud.view.getRouterConfig());

    crud.new.addToMetaProperties('permissions', 'groups/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties('navigation', navigation, crud.new.getRouterConfig());

    crud.edit.addToMetaProperties('permissions', 'groups/edit', crud.edit.getRouterConfig());

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
            label: 'field.parent_group',
            key: 'parent_group_id',
            
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
            label: 'field.parent_group',
            key: 'parent_id',
            render: (h, data) => {
                let [currentGroup, value] = setParentGroup(data)

                return h(Groups, {
                    props: {
                        value,
                        currentGroup,
                        clearable: true,
                    },
                    on: {
                        input(value) {
                            data.inputHandler(value);
                        },
                        setCurrent(group) {
                            data.setValue('_currentGroup', group);
                        },
                    },
                });
            },
            required: false,
        },
    ];

    crud.view.addField(fieldsToShow);
    crud.new.addField(fieldsToFill);
    crud.edit.addField(fieldsToFill);

    context.addRoute(crud.getRouterConfig());

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}