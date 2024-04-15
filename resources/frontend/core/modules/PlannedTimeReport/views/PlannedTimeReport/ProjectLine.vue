<template>
    <div class="project">
        <div class="project__header">
            <div class="row flex-between">
                <h1 class="project__title">{{ project.name }}</h1>
                <span class="h3">{{ formatDurationString(project.total_spent_time) }}</span>
            </div>
            <div class="row">
                <div class="col-12">
                    <span class="h3">{{ $t('planned-time-report.tasks') }}</span>
                </div>
                <div class="col-4">
                    <span class="h3">{{ $t('planned-time-report.estimate') }}</span>
                </div>
                <div class="col-4">
                    <span class="h3">{{ $t('planned-time-report.spent') }}</span>
                </div>
                <div class="col-4">
                    <span class="h3">{{ $t('planned-time-report.productivity') }}</span>
                </div>
            </div>
        </div>
        <at-collapse accordion class="list__item" simple>
            <at-collapse-item v-for="task in project.tasks" :key="task.id" :name="`${task.id}`" class="task list__item">
                <div slot="title" class="row flex-middle">
                    <div class="col-12">
                        <span class="h4">{{ task.task_name }}</span>
                        <div class="task__tags">
                            <at-tag v-if="isOverDue(companyData.timezone, task)" color="error"
                                >{{ $t('tasks.due_date--overdue') }}
                            </at-tag>
                            <at-tag v-if="isOverTime(task)" color="warning"
                                >{{ $t('tasks.estimate--overtime') }}
                            </at-tag>
                        </div>
                    </div>
                    <div class="col-4">
                        <span v-if="task.estimate > 0" class="h4">{{ formatDurationString(task.estimate) }}</span>
                    </div>
                    <div class="col-4">
                        <span class="h4">{{ formatDurationString(task.total_spent_time) }}</span>
                    </div>
                    <div class="col-4 task__progress">
                        <at-progress
                            :percent="getPercentageForProgress(task.total_spent_time, task.estimate)"
                            :status="getProgressStatus(task)"
                            :stroke-width="20"
                            class="flex flex-middle"
                        />
                        <span class="task__progress-percent"
                            >{{ getPercentage(task.total_spent_time, task.estimate) }}%</span
                        >
                    </div>
                </div>

                <div class="row workers">
                    <div v-for="worker in task.workers" :key="`${task.id}-${worker.id}`" class="col-24">
                        <div slot="title" class="row">
                            <div class="col-1">
                                <user-avatar :size="avatarSize" :user="worker.user" />
                            </div>
                            <div class="col-15">
                                <span class="h5">{{ worker.user.full_name }}</span>
                            </div>
                            <div class="col-4">
                                <span class="h4">{{ formatDurationString(worker.duration) }}</span>
                            </div>
                            <div class="col-4">
                                <at-progress
                                    :percent="getPercentageForProgress(worker.duration, task.total_spent_time)"
                                    :stroke-width="15"
                                    status="success"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </at-collapse-item>
        </at-collapse>
    </div>
</template>

<script>
    import moment from 'moment-timezone';
    import UserAvatar from '@/components/UserAvatar';
    import { mapGetters } from 'vuex';
    import { formatDurationString } from '@/utils/time';

    export default {
        name: 'ProjectLine',
        components: {
            UserAvatar,
        },
        data() {
            return {
                openedDates: [],
                avatarSize: 35,
                screenshotsPerRow: 6,
                userTimezone: moment.tz.guess(),
            };
        },
        props: {
            project: {
                type: Object,
                required: true,
            },
        },
        computed: {
            ...mapGetters('user', ['companyData']),
        },
        methods: {
            moment,
            formatDurationString,
            formatDate(value) {
                return moment(value).format('DD.MM.YYYY HH:mm:ss');
            },
            getPercentage(seconds, totalTime) {
                if (!totalTime || !seconds) {
                    return 0;
                }

                return ((seconds * 100) / totalTime).toFixed(2);
            },
            getPercentageForProgress(seconds, totalTime) {
                const percent = this.getPercentage(seconds, totalTime);
                // 99.99% is used coz at-percent component will change status to success for 100%
                return percent >= 100 ? 99.99 : +percent;
            },
            isOverDue(companyTimezone, item) {
                return (
                    typeof companyTimezone === 'string' &&
                    item.due_date != null &&
                    moment.utc(item.due_date).tz(companyTimezone, true).isBefore(moment())
                );
            },
            isOverTime(item) {
                return item.estimate != null && item.total_spent_time > item.estimate;
            },
            getProgressStatus(item) {
                if (this.isOverTime(item) || this.isOverDue(this.companyData.timezone, item)) {
                    return 'error';
                }
                return 'success';
            },
        },
    };
</script>

<style lang="scss" scoped>
    .project {
        &__header {
            display: flex;
            flex-direction: column;
            border-bottom: none;
            padding: 14px 21px;
            row-gap: 1rem;
            border-bottom: 3px solid $blue-3;
        }

        &__title {
            color: $black-900;
            font-size: 2rem;
            font-weight: bold;
        }

        .workers {
            row-gap: 1rem;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
        }
    }

    .task {
        &__tags {
            display: flex;
            column-gap: 0.3rem;
        }

        &__progress {
            position: relative;
        }

        &__progress-percent {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1rem;
            color: #fff;
            filter: drop-shadow(0 0 1px rgba(0, 0, 0, 0.9));
        }
    }
</style>
