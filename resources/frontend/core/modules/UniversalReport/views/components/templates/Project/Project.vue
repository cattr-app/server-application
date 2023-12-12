<template>
    <div>
        <div v-for="(report, id) in formatedReports" :key="id" class="list__item">
            <div slot="title" class="item-header">
                <div>
                    <div class="row flex-middle">
                        <div class="col-xs-10 col-md-10 col-lg-13">
                            <span class="h5">{{ report.name ?? 'No project name selected' }}</span>
                        </div>
                        <div v-if="report.total_spent_time" class="col-xs-offset-3 col-xs-7 col-md-3 col-lg-2">
                            <span class="h4">{{ formatDurationString(report?.total_spent_time ?? 0) }}</span>
                        </div>
                        <div v-if="report.total_spent_time" class="col-xs-5 col-md-9 col-lg-8 d-xs-none">
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
                <BaseInfo class="base-info" :report="report" :charts="charts" :period="period" />
                <template v-if="report.tasks">
                    <h3>{{ `${$t('field.tasks')}:` }}</h3>
                    <TaskInfo v-for="(task, id) in report.tasks" :id="task.id" :key="id" :task="task" />
                </template>
            </div>
        </div>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import BaseInfo from './_components/BaseInfo';
    import TaskInfo from './_components/TaskInfo';

    export default {
        name: 'Project',
        props: {
            reports: {
                type: Object,
                required: true,
                default: () => {},
            },
            charts: {
                type: Object,
                required: true,
                default: () => {},
            },
            period: {
                type: Array,
                required: true,
                default: () => [],
            },
        },
        components: {
            BaseInfo,
            TaskInfo,
        },
        data() {
            return {
                formatedReports: [],
            };
        },
        mounted() {
            this.formatingProjectsReport();
            this.$watch(
                'reports',
                val => {
                    this.formatingProjectsReport();
                },
                {
                    deep: true,
                },
            );
        },
        methods: {
            formatDurationString,
            getUserPercentage(minutes, totalTime) {
                console.log(minutes, totalTime, 'dddddd');
                return Math.floor((minutes * 100) / totalTime);
            },
            formatingProjectsReport() {
                for (let key in this.reports) {
                    let report = this.reports[key];

                    this.$set(this.formatedReports, this.formatedReports.length, {
                        id: key,
                        ...report,
                    });
                }
            },
        },
    };
</script>

<style scoped lang="scss">
    .item-header {
        margin: 16px 0;
    }
    .base-info {
        margin-bottom: 16px;
    }
</style>
