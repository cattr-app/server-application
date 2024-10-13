<template>
    <div>
        <calendar-view class="svg-container svg-container__desktop" :tasks-by-week="tasksByWeek" />
        <calendar-mobile-view
            class="svg-container svg-container__mobile"
            :tasks-by-day="tasksByDay"
            @show-tasks-modal="showTasksModal"
        />

        <tasks-modal :date="modal.date" :tasks="modal.tasks" @close="hideTasksModal" />
    </div>
</template>

<script>
    import moment from 'moment';
    import CalendarMobileView from './CalendarMobileView.vue';
    import CalendarView from './CalendarView.vue';
    import TasksModal from './TasksModal.vue';
    import CalendarService from '../services/calendar.service';

    const ISO8601_DATE_FORMAT = 'YYYY-MM-DD';

    export default {
        components: {
            CalendarMobileView,
            CalendarView,
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
        async created() {
            this.service = new CalendarService();

            const response = await this.service.get(
                moment().startOf('month').format(ISO8601_DATE_FORMAT),
                moment().endOf('month').format(ISO8601_DATE_FORMAT),
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
        methods: {
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
            border: 1px solid #f4f4ff;
            border-radius: 20px;

            box-sizing: border-box;
            box-shadow: 0px 0px 100px rgba(63, 51, 86, 0.05);

            width: 100%;
            height: 100%;

            max-width: 1200px;
        }

        &::v-deep text {
            dominant-baseline: central;
        }
    }
</style>
