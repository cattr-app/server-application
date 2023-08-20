<template>
    <div>
        <at-collapse simple class="list">
            <at-collapse-item v-for="(report, index) in formatedReports" :key="index" class="list__item">
                <div slot="title" class="item-header">
                    <div>
                        <div class="row flex-middle">
                            <div class="col-xs-4 col-md-2 col-lg-1">
                                <UserAvatar :user="report" :size="35" />
                            </div>
                            <div class="col-xs-10 col-md-10 col-lg-13">
                                <span class="h5">{{ report?.full_name ?? 'Not selected name' }}</span>
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
                    <MainInfo :report="report" :period="period" :charts="charts" />
                    <h3 v-if="report.projects">{{ $t('field.projects') }}</h3>
                    <div v-for="(project, id) in report.projects" :key="id">
                        <ProjectInfo :id="id" :project="project" />
                    </div>
                </div>
            </at-collapse-item>
        </at-collapse>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import UserAvatar from '@/components/UserAvatar';
    import MainInfo from './_components/MainInfo';
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
            MainInfo,
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
