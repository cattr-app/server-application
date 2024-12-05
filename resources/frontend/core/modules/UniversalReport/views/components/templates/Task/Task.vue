<template>
    <div>
        <div v-for="(report, id) in formatedReports" :key="id" class="list__item">
            <div slot="title" class="item-header">
                <div>
                    <div class="row flex-middle">
                        <div class="left">
                            <span class="h5">{{ report?.task_name ?? '' }}</span>
                        </div>
                        <div class="center">
                            <span class="h4">{{ formatDurationString(report?.total_spent_time ?? 0) }}</span>
                        </div>
                        <at-progress
                            :percent="getUserPercentage(report?.total_spent_time ?? 0, report?.total_spent_time ?? 0)"
                            class="right time-percentage"
                            status="success"
                            :stroke-width="15"
                        />
                    </div>
                </div>
            </div>
            <div>
                <BaseInfo :report="report" :charts="charts" :period="period" />
            </div>
        </div>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import BaseInfo from './_components/BaseInfo';
    import moment from 'moment';

    export default {
        name: 'Task',
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
                        ...report,
                    });
                }
            },
        },
    };
</script>

<style scoped lang="scss">
    .left {
        flex: 2;
    }
    .center {
        white-space: pre;
    }
    .right {
        flex: 1;

        &::v-deep .at-progress__text {
            display: none;
        }
    }
    .flex-middle {
        gap: 16px;
    }
    .data-entry {
        margin: 16px 0;
    }
    .item-header {
        margin-bottom: 16px;
    }
</style>
