<template>
    <div class="project-reports">
        <h1 class="page-title">{{ $t('navigation.project-report') }}</h1>
        <div class="controls-row">
            <Calendar class="controls-row__item" :sessionStorageKey="sessionStorageKey" @change="onCalendarChange" />

            <UserSelect class="controls-row__item" @change="onUsersSelect" />

            <ProjectSelect class="controls-row__item" @change="onProjectsChange" />

            <div class="controls-row__item controls-row__item--left-auto">
                <small v-if="companyData.timezone">
                    {{ $t('project-report.report_timezone', [companyData.timezone]) }}
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
                <ProjectLine
                    v-for="project in projects"
                    :key="project.id"
                    :project="project"
                    :start="datepickerDateStart"
                    :end="datepickerDateEnd"
                />
            </div>
            <div v-else class="at-container__inner no-data">
                <preloader v-if="isDataLoading" />
                <span>{{ $t('message.no_data') }}</span>
            </div>
        </div>
    </div>
</template>

<script>
    import Calendar from '@/components/Calendar';
    import UserSelect from '@/components/UserSelect';
    import ProjectReportService from '_internal/ProjectReport/services/project-report.service';
    import ProjectLine from './ProjectReport/ProjectLine';
    import {
        getDateToday,
        getStartDate,
        getEndDate,
        formatDurationString,
        getStartOfDayInTimezone,
        getEndOfDayInTimezone,
    } from '@/utils/time';
    import ProjectSelect from '@/components/ProjectSelect';
    import Preloader from '@/components/Preloader';
    import ExportDropdown from '@/components/ExportDropdown';
    import { mapGetters } from 'vuex';
    import debounce from 'lodash.debounce';

    const reportService = new ProjectReportService();

    export default {
        name: 'ProjectReport',
        components: {
            UserSelect,
            Calendar,
            ProjectLine,
            ProjectSelect,
            Preloader,
            ExportDropdown,
        },
        data() {
            const today = getDateToday();
            const sessionStorageKey = 'amazingcat.session.storage.project_report';

            return {
                isDataLoading: false,
                projects: [],
                projectsList: [],
                projectReportsList: {},
                datepickerDateStart: getStartDate(today),
                datepickerDateEnd: getEndDate(today),
                reportTimezone: null,
                userIds: [],
                sessionStorageKey: sessionStorageKey,
            };
        },
        computed: {
            ...mapGetters('user', ['companyData']),
            totalTime() {
                return formatDurationString(this.projects.reduce((acc, cur) => acc + cur.time, 0));
            },
        },
        methods: {
            onUsersSelect(uids) {
                this.userIds = uids;
                this.fetchData();
            },

            onProjectsChange(projectIDs) {
                this.projectsList = projectIDs;
                this.fetchData();
            },
            onCalendarChange({ start, end }) {
                this.datepickerDateStart = getStartDate(start);
                this.datepickerDateEnd = getStartDate(end);
                this.fetchData();
            },
            fetchData: debounce(async function () {
                this.isDataLoading = true;
                try {
                    const { data } = await reportService.getReport(
                        getStartOfDayInTimezone(this.datepickerDateStart, this.companyData.timezone),
                        getEndOfDayInTimezone(this.datepickerDateEnd, this.companyData.timezone),
                        this.userIds,
                        this.projectsList,
                    );

                    this.$set(this, 'projects', data.data);
                } catch ({ response }) {
                    if (process.env.NODE_ENV === 'development') {
                        console.warn(response ? response : 'request to projects is canceled');
                    }
                }

                this.isDataLoading = false;
            }, 350),
            async onExport(format) {
                try {
                    const { data } = await reportService.downloadReport(
                        getStartOfDayInTimezone(this.datepickerDateStart, this.companyData.timezone),
                        getEndOfDayInTimezone(this.datepickerDateEnd, this.companyData.timezone),
                        this.userIds,
                        this.projectsList,
                        format,
                    );

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
