<template>
    <div class="timeline">
        <div class="at-container sidebar">
            <TimelineSidebar
                :active-task="activeTask"
                :isDataLoading="isDataLoading"
                :startDate="start"
                :endDate="end"
            />
        </div>
        <div class="controls-row flex-between">
            <div class="flex">
                <Calendar
                    class="controls-row__item"
                    :range="false"
                    :sessionStorageKey="sessionStorageKey"
                    @change="onCalendarChange"
                />
                <TimezonePicker class="controls-row__item" :value="timezone" @onTimezoneChange="onTimezoneChange" />
            </div>

            <div class="flex">
                <router-link
                    v-if="$can('viewManualTime', 'dashboard')"
                    to="/time-intervals/new"
                    class="controls-row__item"
                >
                    <at-button class="controls-row__btn" icon="icon-edit">
                        {{ $t('control.add_time') }}
                    </at-button>
                </router-link>

                <ExportDropdown
                    class="export-btn dropdown controls-row__btn controls-row__item"
                    position="left-top"
                    trigger="hover"
                    @export="onExport"
                />
            </div>
        </div>

        <div class="at-container intervals">
            <TimelineDayGraph
                v-if="type === 'day'"
                class="graph"
                :start="start"
                :end="end"
                :events="userEvents"
                :timezone="timezone"
                @selectedIntervals="onIntervalsSelect"
                @remove="onBulkRemove"
            />
            <TimelineCalendarGraph v-else class="graph" :start="start" :end="end" :timePerDay="userTimePerDay" />

            <TimelineScreenshots
                v-if="type === 'day' && intervals && Object.keys(intervals).length"
                ref="timelineScreenshots"
                @on-remove="recalculateStatistic"
                @onSelectedIntervals="setSelectedIntervals"
            />
            <preloader v-if="isDataLoading" class="timeline__loader" :is-transparent="true" />

            <TimeIntervalEdit
                :intervals="selectedIntervals"
                @remove="onBulkRemove"
                @edit="loadData"
                @close="clearIntervals"
            />
        </div>
    </div>
</template>

<script>
    import moment from 'moment';
    import debounce from 'lodash/debounce';
    import { mapGetters, mapMutations } from 'vuex';
    import Calendar from '@/components/Calendar';
    import TimelineSidebar from '../../components/TimelineSidebar';
    import TimelineDayGraph from '../../components/TimelineDayGraph';
    import TimelineCalendarGraph from '../../components/TimelineCalendarGraph';
    import TimelineScreenshots from '../../components/TimelineScreenshots';
    import TimezonePicker from '@/components/TimezonePicker';
    import DashboardService from '_internal/Dashboard/services/dashboard.service';
    import { getDateToday } from '@/utils/time';
    import { getStartOfDayInTimezone, getEndOfDayInTimezone } from '@/utils/time';
    import ExportDropdown from '@/components/ExportDropdown';
    import cloneDeep from 'lodash/cloneDeep';
    import TimeIntervalEdit from '../../components/TimeIntervalEdit';
    import Preloader from '@/components/Preloader';

    const updateInterval = 60 * 1000;

    const dashboardService = new DashboardService();

    export default {
        name: 'Timeline',
        components: {
            Calendar,
            TimelineSidebar,
            TimelineDayGraph,
            TimelineCalendarGraph,
            TimelineScreenshots,
            TimezonePicker,
            ExportDropdown,
            TimeIntervalEdit,
            Preloader,
        },
        data() {
            const today = this.getDateToday();
            const sessionStorageKey = 'amazingcat.session.storage.timeline';

            return {
                type: 'day',
                start: today,
                end: today,
                datepickerDateStart: '',
                datepickerDateEnd: '',
                activeTask: +localStorage.getItem('timeline.active-task') || 0,
                showExportModal: false,
                selectedIntervals: [],
                sessionStorageKey: sessionStorageKey,
                isDataLoading: false,
            };
        },
        created() {
            localStorage['dashboard.tab'] = 'timeline';
            this.loadData();
            this.updateHandle = setInterval(() => {
                if (!this.updatedWithWebsockets) {
                    this.loadData(false);
                }

                this.updatedWithWebsockets = false;
            }, updateInterval);
            this.updatedWithWebsockets = false;
        },
        mounted() {
            const channel = this.$echo.private(`intervals.${this.user.id}`);
            channel.listen(`.intervals.create`, data => {
                const startAt = moment.tz(data.model.start_at, 'UTC').tz(this.timezone).format('YYYY-MM-DD');
                const endAt = moment.tz(data.model.end_at, 'UTC').tz(this.timezone).format('YYYY-MM-DD');
                if (startAt > this.end || endAt < this.start) {
                    return;
                }

                this.addInterval(data.model);
                this.updatedWithWebsockets = true;
            });

            channel.listen(`.intervals.edit`, data => {
                this.updateInterval(data.model);
                this.updatedWithWebsockets = true;
            });

            channel.listen(`.intervals.destroy`, data => {
                if (typeof data.model === 'number') {
                    this.removeIntervalById(data.model);
                } else {
                    this.removeInterval(data.model);
                }

                this.updatedWithWebsockets = true;
            });
        },
        beforeDestroy() {
            clearInterval(this.updateHandle);
            this.service.unloadIntervals();

            this.$echo.leave(`intervals.${this.user.id}`);
        },
        computed: {
            ...mapGetters('dashboard', ['service', 'intervals', 'timePerDay', 'timePerProject', 'timezone']),
            ...mapGetters('user', ['user']),
            userEvents() {
                if (!this.user || !this.user.id || !this.intervals[this.user.id]) {
                    return [];
                }

                return this.intervals[this.user.id];
            },
            userTimePerDay() {
                if (!this.user || !this.user.id || !this.timePerDay[this.user.id]) {
                    return {};
                }

                return this.timePerDay[this.user.id];
            },
        },
        methods: {
            getDateToday,
            getStartOfDayInTimezone,
            getEndOfDayInTimezone,
            ...mapMutations({
                setTimezone: 'dashboard/setTimezone',
                removeInterval: 'dashboard/removeInterval',
                addInterval: 'dashboard/addInterval',
                updateInterval: 'dashboard/updateInterval',
                removeIntervalById: 'dashboard/removeIntervalById',
            }),
            loadData: debounce(async function (withLoadingIndicator = true) {
                this.isDataLoading = withLoadingIndicator;

                if (!this.user || !this.user.id) {
                    this.isDataLoading = false;

                    return;
                }

                const userIDs = [this.user.id];

                const startAt = this.getStartOfDayInTimezone(this.start, this.timezone);
                const endAt = this.getEndOfDayInTimezone(this.end, this.timezone);

                await this.service.load(userIDs, null, startAt, endAt, this.timezone);

                this.isDataLoading = false;
            }, 350),
            onCalendarChange({ type, start, end }) {
                this.type = type;
                this.start = start;
                this.end = end;

                this.service.unloadIntervals();

                this.loadData();
            },
            onIntervalsSelect(event) {
                this.selectedIntervals = event ? [event] : [];
            },
            async onExport(format) {
                const { data } = await dashboardService.downloadReport(
                    this.getStartOfDayInTimezone(this.start, this.timezone),
                    this.getEndOfDayInTimezone(this.end, this.timezone),
                    [this.user.id],
                    this.projectIDs,
                    this.timezone,
                    format,
                );

                window.open(data.data.url, '_blank');
            },
            onBulkRemove(intervals) {
                const totalIntervals = cloneDeep(this.intervals);
                intervals.forEach(interval => {
                    const userIntervals = cloneDeep(totalIntervals[interval.user_id]).filter(
                        userInterval => interval.id !== userInterval.id,
                    );
                    const deletedDuration = moment(interval.end_at).diff(interval.start_at, 'seconds');
                    userIntervals.duration -= deletedDuration;

                    totalIntervals[interval.user_id] = userIntervals;
                });
                this.$store.commit('dashboard/setIntervals', totalIntervals);

                this.clearIntervals();
            },
            onTimezoneChange(timezone) {
                this.setTimezone(timezone);
            },
            recalculateStatistic(intervals) {
                this.onBulkRemove(intervals);
            },
            setSelectedIntervals(intervalIds) {
                this.selectedIntervals = intervalIds;
            },
            clearIntervals() {
                if (this.$refs.timelineScreenshots) {
                    this.$refs.timelineScreenshots.clearSelectedIntervals();
                }
                this.selectedIntervals = [];
            },
        },
        watch: {
            user() {
                this.loadData();
            },
            timezone() {
                this.service.unloadIntervals();
                this.loadData();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .at-container::v-deep {
        .modal-screenshot {
            a {
                max-height: inherit;

                img {
                    max-height: inherit;
                    object-fit: fill;
                }
            }
        }
    }
    .at-container {
        padding: 1em;
    }

    .timeline {
        display: grid;
        grid-template-columns: 300px 1fr 1fr;
        column-gap: 0.5rem;
        &__loader {
            z-index: 0;
            border-radius: 20px;
        }
    }

    .timeline-type {
        margin-left: 10px;
        border-radius: 5px;

        .at-btn:first-child {
            border-radius: 5px 0 0 5px;
        }

        .at-btn:last-child {
            border-radius: 0 5px 5px 0;
        }

        &-btn {
            border: 1px solid #eeeef5;
            color: #b1b1be;
            font-size: 15px;
            font-weight: 500;
            height: 40px;

            &.active {
                color: #ffffff;
                background: #2e2ef9;
            }
        }
    }

    .sidebar {
        padding: 30px 0;
        grid-column: 1 / 2;
        grid-row: 1 / 3;
        max-height: fit-content;
        margin-bottom: 0.5rem;
    }

    .controls-row {
        z-index: 1;
        position: relative;
        grid-column: 2 / 4;
        padding-right: 1px; // fix horizontal scroll caused by download btn padding
    }

    .intervals {
        grid-column: 2 / 4;
    }
    @media (max-width: 1300px) {
        .timeline {
            grid-template-columns: 250px 1fr 1fr;
        }
    }
    @media (max-width: 1110px) {
        .canvas {
            padding-top: 0.3rem;
        }
        .at-container {
            padding: 0.5rem;
        }
        .sidebar {
            padding: 15px 0;
        }
        .controls-row {
            flex-direction: column;
            align-items: start;
            //padding-right: 1px; // fix horizontal scroll caused by download btn padding
            &__item {
                margin: 0;
                margin-bottom: $spacing-03;
            }
            .calendar {
                &::v-deep .input {
                    width: unset;
                }
            }
            & > div:first-child {
                display: grid;
                grid-template-columns: repeat(auto-fit, 250px);
                width: 100%;
                column-gap: $spacing-03;
            }
            & > div:last-child {
                align-self: flex-end;
                column-gap: $spacing-03;
            }
        }
    }
    @media (max-width: 790px) {
        .intervals {
            grid-column: 1/4;
        }
        .controls-row {
            & > div:last-child {
                align-self: start;
            }
        }
    }
    @media (max-width: 560px) {
        .controls-row {
            grid-column: 1/4;
            grid-row: 1;
            & > div:last-child {
                align-self: end;
            }
        }
        .sidebar {
            grid-row: 2;
        }
    }

    .graph {
        width: 100%;
    }
</style>
