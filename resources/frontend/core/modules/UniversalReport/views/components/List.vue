<template>
    <div v-if="Object.keys(data.reportData).length">
        <User
            v-if="hasBase('user')"
            :reports="data.reportData"
            :charts="data.reportCharts"
            :period="data.periodDates"
        />
        <Task
            v-else-if="hasBase('task')"
            :reports="data.reportData"
            :charts="data.reportCharts"
            :period="data.periodDates"
        />
        <Project
            v-else-if="hasBase('project')"
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
    import { hasSelectedBase } from '@/utils/universal-report';
    import User from './templates/User/User';
    import Task from './templates/Task/Task';
    import Project from './templates/Project/Project';

    const service = new UniversalReportService();

    export default {
        name: 'List',
        props: {
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
            ...mapGetters('universalreport', ['selectedBase']),
        },
        async created() {
            const { data } = await service.show(this.$route.params.id);

            this.setName(data.data.name);
            this.setBase(data.data.base);
            this.setSelectedFields(data.data.fields);
            this.setCalendarData({
                type: sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.type') ?? 'day',
                end:
                    sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.end') ??
                    moment().format('YYYY-MM-DD'),
                start:
                    sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.start') ??
                    moment().format('YYYY-MM-DD'),
            });
        },
        methods: {
            ...mapMutations({
                setName: 'universalreport/setName',
                setBase: 'universalreport/setBase',
                setCalendarData: 'universalreport/setCalendarData',
                setSelectedFields: 'universalreport/setSelectedFields',
            }),

            getUserPercentage(minutes, totalTime) {
                return Math.floor((minutes * 100) / totalTime);
            },
            hasBase(value) {
                return hasSelectedBase(value);
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
