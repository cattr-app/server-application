<template>
    <div>
        <at-collapse simple class="list">
            <at-collapse-item v-for="(report, id) in formatedReports" :key="id" class="list__item">
                <div slot="title" class="item-header">
                    <div>
                        <div class="row flex-middle">
                            <div class="col-xs-10 col-md-10 col-lg-13">
                                <span class="h5">{{ report?.name ?? '' }}</span>
                            </div>
                            <div class="col-xs-offset-3 col-xs-7 col-md-3 col-lg-2">
                                <span class="h4">{{ formatDurationString(report?.total_spent_time ?? 0) }}</span>
                            </div>
                            <div class="col-xs-5 col-md-9 col-lg-8 d-xs-none">
                                <at-progress
                                    :percent="
                                        getUserPercentage(report?.total_spent_time ?? 0, report?.total_spent_time ?? 0)
                                    "
                                    class="time-percentage"
                                    status="success"
                                    :stroke-width="15"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="data-entries">
                        <h3>Информация о задаче:</h3>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.name') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.name }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.status') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.status }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.priority') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.priority }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.description') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.description }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <h3>{{ $t('field.members') }}</h3>
                            <at-table
                                :columns="[
                                    { title: $t('field.full_name'), key: 'full_name' },
                                    { title: $t('field.email'), key: 'email' },
                                    { title: $t('field.total_spent'), key: 'total_spent_time_by_user' },
                                ]"
                                :data="report.users"
                            />
                        </div>
                        <div class="data-entry">
                            <h4>Отработано по дням:</h4>
                            <div v-for="(timeDay, day) in report.worked_time_day" :key="day" class="row">
                                <div class="col-6 label">{{ day }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ formatDurationString(timeDay) }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <h3>Информация о проекте:</h3>
                        <div v-for="(value, field) in report.project" :key="field" class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t(`field.${field}`) }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ value }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </at-collapse-item>
        </at-collapse>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    // import UserAvatar from '@/components/UserAvatar';
    import { Skeleton } from 'vue-loading-skeleton';

    export default {
        name: 'Task',
        props: {
            reports: {
                type: Object,
                required: true,
                default: () => {},
            },
        },
        components: {
            // UserAvatar,
            Skeleton,
        },
        data() {
            return {
                formatedReports: [],
            };
        },
        mounted() {
            this.formatingTasksReport();
            this.$watch(
                'reports',
                val => {
                    console.log(1);
                    this.formatingTasksReport();
                },
                {
                    deep: true,
                },
            );
        },
        methods: {
            formatDurationString,
            getUserPercentage(minutes, totalTime) {
                return Math.floor((minutes * 100) / totalTime);
            },
            formatingTasksReport() {
                for (let key in this.reports) {
                    let report = this.reports[key];
                    this.$set(this.formatedReports, this.formatedReports.length, {
                        id: key,
                        description: report.description,
                        due_date: report.due_date,
                        estimate: report.estimate,
                        priority: report.priority,
                        status: report.status,
                        name: report.task_name,
                        total_spent_time: report.total_spent_time,
                        project: report.project,
                        users: Object.values(report.users),
                        worked_time_day: report.worked_time_day,
                    });
                }
            },
        },
    };
</script>
