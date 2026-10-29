import TimeIntervalService from '@/services/resource/time-interval.service';
import UsersService from '@/services/resource/user.service';
import TasksService from '@/services/resource/task.service';
import LazySelect from './components/LazySelect';
import DatetimeInput from './components/DatetimeInput';
import TimezonePicker from '@/components/TimezonePicker';
import { store as rootStore } from '@/store';
import moment from 'moment-timezone';

export const ModuleConfig = {
    routerPrefix: 'time-intervals',
    loadOrder: 20,
    moduleName: 'TimeIntervals',
};

export function init(context, router) {
    const crud = context.createCrud('time_intervals.crud_title', 'time-intervals', TimeIntervalService);

    crud.new.addToMetaProperties('permissions', 'time-intervals/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties(
        'afterSubmitCallback',
        () => {
            const { task_id, from_task } = router.currentRoute.query;
            const id = Number(task_id);
            if (from_task === '1' && Number.isSafeInteger(id) && id > 0) {
                return router.push({ path: '/tasks/view/' + id });
            }
            return router.go(-1);
        },
        crud.new.getRouterConfig(),
    );

    const fieldsToFill = [
        {
            key: 'is_manual',
            default: true,
            displayable: false,
        },
        {
            label: 'field.user',
            key: 'user_id',
            type: 'resource-select',
            service: new UsersService(),
            default: store => {
                const taskId = router.currentRoute.query.task_id;
                return taskId === undefined ? undefined : store.getters['user/user'].id;
            },
            required: true,
        },
        {
            label: 'field.task',
            key: 'task_id',
            render: (h, props) => {
                return h(LazySelect, {
                    props: {
                        service: new TasksService(),
                        inputHandler: props.inputHandler,
                        value: props.currentValue,
                        userID: props.values.user_id,
                        preselectedId: router.currentRoute.query.task_id,
                    },
                });
            },
            required: true,
        },
        {
            label: 'field.timezone',
            key: 'timezone',
            render: (h, props) => {
                const timezone =
                    typeof props.currentValue === 'string'
                        ? props.currentValue
                        : rootStore.getters['dashboard/timezone'] || moment.tz.guess();

                props.setValue('timezone', timezone);

                return h(TimezonePicker, {
                    props: {
                        value: timezone,
                    },
                    on: {
                        onTimezoneChange: value => {
                            props.setValue('timezone', value);
                            rootStore.commit('dashboard/setTimezone', value);
                        },
                    },
                });
            },
            required: true,
        },
        {
            label: 'field.start_at',
            key: 'start_at',
            render: (h, props) => {
                const value =
                    typeof props.currentValue === 'string'
                        ? moment(props.currentValue).tz(props.values.timezone, true).toISOString()
                        : moment().startOf('day').tz(props.values.timezone, true).toISOString();

                return h(DatetimeInput, {
                    props: {
                        inputHandler: props.inputHandler,
                        value,
                        timezone: props.values.timezone,
                    },
                    on: {
                        change: value => {
                            value = moment(value).add(10, 'minutes').toISOString();
                            props.setValue('end_at', value);

                            // Set is_manual for task here, because it`s stupid to fill it manually each time
                            props.setValue('is_manual', true);
                        },
                    },
                });
            },
            required: true,
        },
        {
            label: 'field.end_at',
            key: 'end_at',
            render: (h, props) => {
                const value =
                    typeof props.currentValue === 'string'
                        ? moment(props.currentValue).tz(props.values.timezone, true).toISOString()
                        : moment().startOf('day').tz(props.values.timezone, true).add(10, 'minutes').toISOString();

                return h(DatetimeInput, {
                    props: {
                        inputHandler: props.inputHandler,
                        value,
                        timezone: props.values.timezone,
                    },
                });
            },
            required: true,
        },
    ];

    crud.new.addField(fieldsToFill);

    context.addRoute(crud.getRouterConfig());

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
