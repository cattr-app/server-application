<template>
    <div>
        <div v-for="(report, index) in formatedReports" :key="index" class="list__item">
            <div slot="title" class="item-header">
                <div>
                    <div class="row flex-middle">
                        <div class="col-xs-4 col-md-2 col-lg-1">
                            <UserAvatar :user="report" :size="35" />
                        </div>
                        <div class="left">
                            <span class="h5">{{ report?.full_name ?? 'Not selected name' }}</span>
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
                <BaseInfo :report="report" :period="period" :charts="charts" />
                <h3 v-if="report.projects">{{ $t('field.projects') }}</h3>
                <div v-for="(project, id) in report.projects" :key="id">
                    <ProjectInfo :id="project.id" :project="project" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import UserAvatar from '@/components/UserAvatar';
    import BaseInfo from './_components/BaseInfo';
    import ProjectInfo from './_components/ProjectInfo';

    export default {
        name: 'User',
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
            UserAvatar,
            BaseInfo,
            ProjectInfo,
        },
        data() {
            return {
                formatedReports: [],
            };
        },
        async mounted() {
            this.formatingUsersReport();
            this.$watch(
                'this.reports',
                val => {
                    console.log(1);
                    this.formatingUsersReport();
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
            formatingUsersReport() {
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
