<template>
    <div class="screenshots">
        <h1 class="page-title">{{ $t('navigation.screenshots') }}</h1>
        <div class="controls-row">
            <div class="controls-row__item">
                <Calendar :sessionStorageKey="sessionStorageKey" @change="onCalendarChange" />
            </div>
            <div class="controls-row__item">
                <UserSelect @change="onUsersChange" />
            </div>
            <div class="controls-row__item">
                <ProjectSelect @change="onProjectsChange" />
            </div>

            <div class="controls-row__item">
                <TimezonePicker :value="timezone" @onTimezoneChange="onTimezoneChange" />
            </div>
        </div>
        <div class="at-container">
            <div class="at-container__inner">
                <template v-if="this.intervals.length > 0">
                    <div class="row">
                        <div v-for="interval in this.intervals" :key="interval.id" class="col-4 screenshots__card">
                            <Screenshot
                                class="screenshot"
                                :disableModal="true"
                                :interval="interval"
                                :task="interval.task"
                                :user="modal.user"
                                :timezone="timezone"
                                @click="showImage(interval)"
                            />
                        </div>
                    </div>

                    <ScreenshotModal
                        :project="modal.project"
                        :interval="modal.interval"
                        :show="modal.show"
                        :showNavigation="true"
                        :task="modal.task"
                        :user="modal.user"
                        @close="onHide"
                        @remove="removeScreenshot"
                        @showNext="showNext"
                        @showPrevious="showPrevious"
                    />
                </template>

                <div v-else class="no-data">
                    <span>{{ $t('message.no_data') }}</span>
                </div>
                <preloader v-if="isDataLoading"></preloader>
            </div>
        </div>
        <div class="screenshots__pagination">
            <at-pagination
                :total="intervalsTotal"
                :current="page"
                :page-size="limit"
                @page-change="loadPage"
            ></at-pagination>
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapMutations } from 'vuex';
    import Calendar from '@/components/Calendar';
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import UserSelect from '@/components/UserSelect';
    import ProjectService from '@/services/resource/project.service';
    import TimeIntervalService from '@/services/resource/time-interval.service';
    import { getStartOfDayInTimezone, getEndOfDayInTimezone, getDateWithTimezoneDifference } from '@/utils/time';
    import Preloader from '@/components/Preloader';
    import ProjectSelect from '@/components/ProjectSelect';
    import TimezonePicker from '@/components/TimezonePicker';

    export default {
        name: 'Screenshots',
        components: {
            Calendar,
            Screenshot,
            ScreenshotModal,
            UserSelect,
            Preloader,
            ProjectSelect,
            TimezonePicker,
        },
        data() {
            const limit = 15;
            const localStorageKey = 'user-select.users';
            const sessionStorageKey = 'amazingcat.session.storage.screenshots';

            return {
                intervals: [],
                userIDs: null,
                projectsList: [],
                datepickerDateStart: '',
                datepickerDateEnd: '',
                projectService: new ProjectService(),
                intervalService: new TimeIntervalService(),
                modal: {
                    show: false,
                },
                limit: limit,
                page: 1,
                intervalsTotal: 0,
                localStorageKey: localStorageKey,
                sessionStorageKey: sessionStorageKey,
                isDataLoading: false,
            };
        },
        computed: {
            ...mapGetters('dashboard', ['timezone']),
            ...mapGetters('timeline', ['service', 'users']),
            ...mapGetters('user', ['user', 'companyData']),
        },
        watch: {
            companyData() {
                this.getScreenshots();
            },
            timezone() {
                this.getScreenshots();
            },
        },
        async created() {
            window.addEventListener('keydown', this.onKeyDown);
            try {
                await this.getScreenshots();
            } catch ({ response }) {
                if (process.env.NODE_ENV === 'development') {
                    console.log(response ? response : 'request to screenshots is canceled');
                }
            }
        },
        beforeDestroy() {
            window.removeEventListener('keydown', this.onKeyDown);
        },
        methods: {
            getStartOfDayInTimezone,
            getEndOfDayInTimezone,
            ...mapMutations({
                setTimezone: 'dashboard/setTimezone',
            }),
            onTimezoneChange(timezone) {
                this.setTimezone(timezone);
            },
            onHide() {
                this.modal.show = false;
            },
            onKeyDown(e) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.showPrevious();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.showNext();
                }
            },
            showPrevious() {
                const currentIndex = this.intervals.findIndex(x => x.id === this.modal.interval.id);

                if (currentIndex !== 0) {
                    this.modal.interval = this.intervals[currentIndex - 1];
                }
            },
            showNext() {
                const currentIndex = this.intervals.findIndex(x => x.id === this.modal.interval.id);

                if (currentIndex + 1 !== this.intervals.length) {
                    this.modal.interval = this.intervals[currentIndex + 1];
                }
            },
            showImage(interval) {
                this.modal = {
                    interval,
                    user: interval.user,
                    task: interval.task,
                    project: interval.task?.project,
                    show: true,
                };
            },
            onUsersChange(userIDs) {
                this.userIDs = userIDs;
                if (this._isMounted) {
                    this.getScreenshots();
                }
            },
            onProjectsChange(projectIDs) {
                this.projectsList = projectIDs;
                if (this._isMounted) {
                    this.getScreenshots();
                }
            },
            onCalendarChange({ start, end }) {
                this.datepickerDateStart = start;
                this.datepickerDateEnd = end;
                this.getScreenshots();
            },
            async getScreenshots() {
                if (
                    this.userIDs === 'undefined' ||
                    !this.datepickerDateStart ||
                    !this.datepickerDateEnd ||
                    !this.timezone ||
                    !this.companyData.timezone
                ) {
                    return;
                }

                this.isDataLoading = true;

                const startAt = getDateWithTimezoneDifference(
                    this.datepickerDateStart,
                    this.companyData.timezone,
                    this.timezone,
                );
                const endAt = getDateWithTimezoneDifference(
                    this.datepickerDateEnd,
                    this.companyData.timezone,
                    this.timezone,
                    false,
                );

                try {
                    const { data } = await this.intervalService.getAll({
                        where: {
                            user_id: ['in', this.userIDs],
                            'task.project_id': ['in', this.projectsList],
                            start_at: ['between', [startAt, endAt]],
                        },
                        page: this.page,
                        with: ['task', 'task.project', 'user'],
                    });
                    this.intervalsTotal = data.pagination.total;
                    this.intervals = data.data;
                } catch ({ response }) {
                    return;
                }

                this.isDataLoading = false;
            },
            async removeScreenshot(id) {
                try {
                    await this.intervalService.deleteItem(id);
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.screenshot.delete.success.title'),
                        message: this.$t('notification.screenshot.delete.success.message'),
                    });

                    this.intervals = this.intervals.filter(interval => interval.id !== id);
                    this.onHide();
                } catch (e) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.screenshot.delete.error.title'),
                        message: this.$t('notification.screenshot.delete.error.message'),
                    });
                }
            },
            async loadPage(page) {
                this.page = page;
                await this.getScreenshots();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .at-container {
        overflow: hidden;
        margin-bottom: $layout-01;

        &__inner {
            position: relative;
        }
    }

    .screenshots {
        &__card {
            margin-bottom: $layout-02;
            cursor: pointer;
        }

        &__pagination {
            flex-direction: row-reverse;
            display: flex;
        }
    }

    .screenshot::v-deep {
        .screenshot-image {
            img {
                height: 150px;
                border-radius: 5px;
            }
        }
    }

    .no-data {
        position: relative;
        text-align: center;
        font-weight: bold;
    }
</style>
