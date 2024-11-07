<template>
    <div class="calendar">
        <h1 class="page-title">{{ $t('navigation.calendar') }}</h1>

        <div class="controls-row">
            <DatePicker
                class="controls-row__item"
                :day="false"
                :week="false"
                :range="false"
                initialTab="month"
                @change="onDateChange"
            />

            <ProjectSelect class="controls-row__item" @change="onProjectsChange" />

            <at-button class="controls-row__item show-all" @click="onShowAllClick">{{
                showAll ? $t('calendar.show_first') : $t('calendar.show_all')
            }}</at-button>
        </div>

        <div class="at-container">
            <calendar-view
                class="svg-container svg-container__desktop"
                :tasks-by-week="tasksByWeek"
                :show-all="showAll"
            />

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
    import ProjectSelect from '@/components/ProjectSelect';
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
            ProjectSelect,
        },
        data() {
            return {
                start: moment().startOf('month').format(ISO8601_DATE_FORMAT),
                end: moment().endOf('month').format(ISO8601_DATE_FORMAT),
                projects: [],

                tasksByDay: [],
                tasksByWeek: [],

                modal: {
                    date: moment().format(ISO8601_DATE_FORMAT),
                    tasks: [],
                },

                showAll: false,
            };
        },
        created() {
            this.service = new CalendarService();
            this.load();
        },
        methods: {
            async load() {
                const response = await this.service.get(this.start, this.end, this.projects);
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
            onDateChange({ start, end }) {
                this.start = moment(start).startOf('month').format(ISO8601_DATE_FORMAT);
                this.end = moment(end).endOf('month').format(ISO8601_DATE_FORMAT);
                this.load();
            },
            onProjectsChange(projects) {
                this.projects = projects;
                this.load();
            },
            showTasksModal({ date, tasks }) {
                this.modal.date = date;
                this.modal.tasks = tasks;
            },
            hideTasksModal() {
                this.modal.tasks = [];
            },
            onShowAllClick() {
                this.showAll = !this.showAll;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .show-all {
        display: none;

        @media screen and (min-width: 768px) {
            display: block;
        }
    }

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

    .controls-row {
        flex-flow: row wrap;
    }
</style>
