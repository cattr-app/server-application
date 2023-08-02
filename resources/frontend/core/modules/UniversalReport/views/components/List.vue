<template>
    <div v-if="Object.keys(reportsList).length">
        <User v-if="hasMain('user')" :reports="reportsList" />
        <Task v-else-if="hasMain('task')" :reports="reportsList" />
        <Project v-else-if="hasMain('project')" :reports="reportsList" />
    </div>
</template>

<script>
    import { mapGetters, mapMutations } from 'vuex';
    import UniversalReportService from '../../service/universal-report.service';
    import moment from 'moment';
    import { hasSelectedMain } from '@/utils/universal-report';
    import User from '../templates/User';
    import Task from '../templates/Task';
    import Project from '../templates/Project';

    const service = new UniversalReportService();

    export default {
        name: 'List',
        props: {
            reportsList: {
                type: Object,
                required: true,
                default: () => {},
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
