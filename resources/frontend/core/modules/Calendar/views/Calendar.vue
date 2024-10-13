<template>
    <div class="calendar">
        <h1 class="page-title">{{ $t('navigation.calendar') }}</h1>

        <div class="controls-row">
            <div class="controls-row__item">
                <DatePicker :day="false" :week="false" :range="false" initialTab="month" @change="onDateChange" />
            </div>

            <!-- <div class="select controls-row__item">
                <UserSelect @change="onUsersChange" />
            </div> -->
        </div>

        <div class="at-container">
            <calendar-view class="svg-container svg-container__desktop" :tasks-by-week="tasksByWeek" />

            <calendar-mobile-view
                class="svg-container svg-container__mobile"
                :tasks-by-day="tasksByDay"
                @show-tasks-modal="showTasksModal"
            />

            <tasks-modal :date="modal.date" :tasks="modal.tasks" @close="hideTasksModal" />
        </div>
    </div>
</template>

<script>
    import moment from 'moment';
    import DatePicker from '@/components/Calendar';
    import CalendarMobileView from '../components/CalendarMobileView.vue';
    import CalendarView from '../components/CalendarView.vue';
    import TasksModal from '../components/TasksModal.vue';
    import CalendarService from '../services/calendar.service';

    const ISO8601_DATE_FORMAT = 'YYYY-MM-DD';

    export default {
        components: {
            CalendarMobileView,
            CalendarView,
            DatePicker,
            TasksModal,
        },
        data() {
            return {
                tasksByDay: [],
                tasksByWeek: [],
                modal: {
                    date: moment().format(ISO8601_DATE_FORMAT),
                    tasks: [],
                },
            };
        },
        created() {
            this.service = new CalendarService();
            this.load(moment(), moment());
        },
        methods: {
            async load(start, end) {
                const response = await this.service.get(
                    moment(start).startOf('month').format(ISO8601_DATE_FORMAT),
                    moment(end).endOf('month').format(ISO8601_DATE_FORMAT),
                );

                const { tasks, tasks_by_day, tasks_by_week } = response.data.data;

                this.tasksByDay = tasks_by_day.map(day => ({
                    ...day,
                    tasks: day.task_ids.map(task_id => tasks[task_id]),
                }));

                this.tasksByWeek = tasks_by_week.map(week => ({
                    ...week,
                    tasks: week.tasks.map(item => ({
                        ...item,
                        task: tasks[item.task_id],
                    })),
                }));
            },
            onDateChange({ type, start, end }) {
                this.load(start, end);
            },
            showTasksModal({ date, tasks }) {
                this.modal.date = date;
                this.modal.tasks = tasks;
            },
            hideTasksModal() {
                this.modal.tasks = [];
            },
        },
    };
</script>

<style lang="scss" scoped>
    .svg-container {
        align-items: center;
        justify-content: center;

        &__mobile {
            display: flex;

            @media screen and (min-width: 768px) {
                display: none;
            }
        }

        &__desktop {
            display: none;

            @media screen and (min-width: 768px) {
                display: flex;
            }
        }

        &::v-deep svg {
            width: 100%;
            height: 100%;
        }

        &::v-deep text {
            dominant-baseline: central;
        }
    }
</style>
