<template>
    <div v-if="Object.keys(data.reportData).length">
        <User
            v-if="hasMain('user')"
            :reports="data.reportData"
            :charts="data.reportCharts"
            :period="data.periodDates"
        />
        <Task
            v-else-if="hasMain('task')"
            :reports="data.reportData"
            :charts="data.reportCharts"
            :period="data.periodDates"
        />
        <Project
            v-else-if="hasMain('project')"
            :reports="data.reportData"
            :charts="data.reportCharts"
            :period="data.periodDates"
        />
    </div>
</template>

<script>
    import { mapGetters, mapMutations } from 'vuex';
    import UniversalReportService from '../../service/universal-report.service';
    import moment from 'moment';
    import { hasSelectedMain } from '@/utils/universal-report';
    import User from './templates/User/User';
    import Task from './templates/Task/Task';
    import Project from './templates/Project/Project';

    const service = new UniversalReportService();

    export default {
        name: 'List',
        props: {
            // reportsList: {
            //     type: Object,
            //     required: true,
            //     default: () => {},
            // },
            // reportsCharts: {
            //     type: Object,
            //     required: true,
            //     default: () => {},
            // },
            data: {
                type: Object,
                required: true,
                default: function () {
                    return {
                        reportData: {},
                        reportCharts: {},
                        reportName: '',
                    };
                },
            },
        },
        components: {
            User,
            Task,
            Project,
        },
        data() {
            return {
                reports: [],
            };
        },
        computed: {
            ...mapGetters('universalreport', ['selectedMain']),
        },
        async mounted() {
            await service.show(this.$route.params.id).then(({ data }) => {
                this.setName(data.data.name);
                this.setMain(data.data.main);
                this.setCalendarData({
                    type: sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.type') ?? 'day',
                    end:
                        sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.end') ??
                        moment().format('YYYY-MM-DD'),
                    start:
                        sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.start') ??
                        moment().format('YYYY-MM-DD'),
                });
            });
        },
        methods: {
            ...mapMutations({
                setName: 'universalreport/setName',
                setMain: 'universalreport/setMain',
                setCalendarData: 'universalreport/setCalendarData',
                // clearStore: 'universalreport/clearStore',
            }),

            getUserPercentage(minutes, totalTime) {
                return Math.floor((minutes * 100) / totalTime);
            },
            hasMain(value) {
                return hasSelectedMain(value);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .data-entries {
        margin-bottom: 20px;
    }
    .at-collapse {
        color: $gray-2;

        &__content .at-collapse__header {
            cursor: default;
        }
    }

    .link {
        color: $gray-2;

        &:hover {
            color: $gray-1;
        }
    }

    .text-ellipsis {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media screen and (max-width: 991px) {
        .d-xs-none {
            display: none;
        }
    }
</style>
