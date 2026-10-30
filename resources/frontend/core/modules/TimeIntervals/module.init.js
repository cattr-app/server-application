import TimeIntervalService from '@/services/resource/time-interval.service';
import UsersService from '@/services/resource/user.service';
import TasksService from '@/services/resource/task.service';
import LazySelect from './components/LazySelect';
import DatetimeInput from './components/DatetimeInput';
import DurationInput from './components/DurationInput';
import TimezonePicker from '@/components/TimezonePicker';
import { store as rootStore } from '@/store';
import moment from 'moment-timezone';
import i18n from '@/i18n';
import { extend } from 'vee-validate';
import {
    DEFAULT_DURATION_MINUTES,
    MAX_DURATION_MINUTES,
    createDurationHandlers,
    toIntervalPayload,
    validateDuration,
} from './duration';

export const ModuleConfig = {
    routerPrefix: 'time-intervals',
    loadOrder: 20,
    moduleName: 'TimeIntervals',
};

class ManualTimeIntervalService extends TimeIntervalService {
    save(data) {
        return super.save(toIntervalPayload(data));
    }
}

extend('manual_duration', value => {
    const error = validateDuration(value);

    return error === null
        ? true
        : i18n.t(`time_intervals.duration.errors.${error}`, {
              max: MAX_DURATION_MINUTES,
              hours: MAX_DURATION_MINUTES / 60,
          });
});

export function init(context, router) {
    const crud = context.createCrud('time_intervals.crud_title', 'time-intervals', new ManualTimeIntervalService());

    crud.new.addToMetaProperties('permissions', 'time-intervals/create', crud.new.getRouterConfig());
    crud.new.addToMetaProperties(
        'afterSubmitCallback',
        () => {
            const { task_id, from_task } = router.currentRoute.query;
            const id = Number(task_id);
            if (from_task === '1' && Number.isSafeInteger(id) && id > 0) {
                return router.replace({ path: '/tasks/view/' + id });
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
                        // RenderableField supplies an empty object for an unset field.
                        value: typeof props.currentValue === 'number' ? props.currentValue : null,
                        userID: props.values.user_id,
                        currentUserId: rootStore.getters['user/user'].id,
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
                            createDurationHandlers(props.values, props.setValue).onStartChange(value);

                            // Set is_manual for task here, because it`s stupid to fill it manually each time
                            props.setValue('is_manual', true);
                        },
                    },
                });
            },
            required: true,
        },
        {
            label: 'time_intervals.duration.label',
            key: 'duration',
            default: DEFAULT_DURATION_MINUTES,
            rules: 'required|manual_duration',
            // Duration also changes when start/end are picked, so show its errors right away.
            immediate: true,
            render: (h, props) => {
                return h(DurationInput, {
                    props: {
                        inputHandler: props.inputHandler,
                        value:
                            typeof props.currentValue === 'number' || typeof props.currentValue === 'string'
                                ? props.currentValue
                                : DEFAULT_DURATION_MINUTES,
                    },
                    on: {
                        change: value => createDurationHandlers(props.values, props.setValue).onDurationChange(value),
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
                        : moment()
                              .startOf('day')
                              .tz(props.values.timezone, true)
                              .add(DEFAULT_DURATION_MINUTES, 'minutes')
                              .toISOString();

                return h(DatetimeInput, {
                    props: {
                        inputHandler: props.inputHandler,
                        value,
                        timezone: props.values.timezone,
                    },
                    on: {
                        change: (value, meta) =>
                            createDurationHandlers(props.values, props.setValue).onEndChange(value, meta),
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
