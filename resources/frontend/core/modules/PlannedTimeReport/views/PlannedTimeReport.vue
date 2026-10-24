<template>
    <div class="project-reports">
        <h1 class="page-title">{{ $t('navigation.planned-time-report') }}</h1>
        <div class="controls-row">
            <ProjectSelect class="controls-row__item" @change="onProjectsChange" />

            <div class="controls-row__item controls-row__item--left-auto">
                <small v-if="reportDate">
                    {{ $t('planned-time-report.report_date', [reportDate]) }}
                </small>
            </div>

            <ExportDropdown
                class="export-btn dropdown controls-row__btn controls-row__item"
                position="left-top"
                trigger="hover"
                @export="onExport"
            />
        </div>

        <div class="at-container">
            <div class="total-time-row">
                <span class="total-time-label">{{ $t('field.total_time') }}</span>
                <span class="total-time-value">{{ totalTime }}</span>
            </div>

            <div v-if="Object.keys(projects).length && !isDataLoading">
                <ProjectLine v-for="project in projects" :key="project.id" :project="project" />
            </div>
            <div v-else class="at-container__inner no-data">
                <preloader v-if="isDataLoading" />
                <span>{{ $t('message.no_data') }}</span>
            </div>
        </div>
    </div>
</template>

<script>
    import PlannedTimeReport from '../service/planned-time-report';
    import ProjectLine from './PlannedTimeReport/ProjectLine';
    import { formatDurationString } from '@/utils/time';
    import ProjectSelect from '@/components/ProjectSelect';
    import Preloader from '@/components/Preloader';
    import ExportDropdown from '@/components/ExportDropdown';
    import { mapGetters } from 'vuex';
    import debounce from 'lodash.debounce';

    const reportService = new PlannedTimeReport();

    export default {
        name: 'PlannedTimeReport',
        components: {
            ProjectLine,
            ProjectSelect,
            Preloader,
            ExportDropdown,
        },
        data() {
            return {
                isDataLoading: false,
                projects: [],
                reportDate: null,
                projectsList: [],
                projectReportsList: {},
                userIds: [],
            };
        },
        computed: {
            ...mapGetters('user', ['companyData']),
            totalTime() {
                return formatDurationString(this.projects.reduce((acc, proj) => acc + proj.total_spent_time, 0));
            },
        },
        methods: {
            onProjectsChange(projectIDs) {
                this.projectsList = projectIDs;
                this.fetchData();
            },
            fetchData: debounce(async function () {
                this.isDataLoading = true;
                try {
                    const { data } = await reportService.getReport(this.projectsList);

                    this.$set(this, 'projects', data.data.reportData);
                    this.$set(this, 'reportDate', data.data.reportDate);
                } catch ({ response }) {
                    if (process.env.NODE_ENV === 'development') {
                        console.warn(response ? response : 'request to projects is canceled');
                    }
                }

                this.isDataLoading = false;
            }, 350),
            async onExport(format) {
                try {
                    const { data } = await reportService.downloadReport(this.projectsList, format);

                    window.open(data.data.url, '_blank');
                } catch ({ response }) {
                    if (process.env.NODE_ENV === 'development') {
                        console.log(response ? response : 'request to reports is canceled');
                    }
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .at-container {
        overflow: hidden;
    }

    .total-time-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 21px;
        color: $black-900;
        font-size: 2rem;
        font-weight: bold;
    }

    .no-data {
        text-align: center;
        font-weight: bold;
        position: relative;
    }

    .project-select {
        width: 240px;
    }
</style>
