<template>
    <div>
        <calendar-mobile-view class="svg-container svg-container__mobile" :tasks-by-day="tasksByDay" />
        <calendar-view class="svg-container svg-container__desktop" :tasks-by-week="tasksByWeek" />
    </div>
</template>

<script>
    import moment from 'moment';
    import CalendarMobileView from './CalendarMobileView.vue';
    import CalendarView from './CalendarView.vue';
    import CalendarService from '../services/calendar.service';

    export default {
        components: {
            CalendarMobileView,
            CalendarView,
        },
        data() {
            return {
                tasksByDay: [],
                tasksByWeek: [],
            };
        },
        created() {
            this.service = new CalendarService();
            this.service
                .get(moment().startOf('month').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD'))
                .then(response => {
                    this.tasksByDay = Object.values(response.data.data.tasks_by_day).map(dayItem => ({
                        ...dayItem,
                        tasks: dayItem.task_ids.map(task_id => response.data.data.tasks[task_id]),
                    }));

                    this.tasksByWeek = Object.values(response.data.data.tasks_by_week).map(weekData => ({
                        ...weekData,
                        tasks: weekData.tasks.map(taskData => ({
                            ...taskData,
                            task: response.data.data.tasks[taskData.task_id],
                        })),
                    }));
                });
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
