<template>
    <div class="project">
        <div class="project__header">
            <h1 class="project__title">{{ project.name }}</h1>
            <span class="h3">{{ formatDurationString(project.time) }}</span>
        </div>
        <at-collapse simple class="list__item">
            <at-collapse-item v-for="user in project.users" :key="user.id" class="list__item">
                <div slot="title" class="row flex-middle">
                    <div class="col-3 col-xs-2 col-md-1">
                        <user-avatar :user="user" :size="avatarSize" />
                    </div>
                    <div class="col-8 col-md-10 col-lg-11">
                        <span class="h5">{{ user.full_name }}</span>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <span class="h4">{{ formatDurationString(user.time) }}</span>
                    </div>
                    <div class="col-10">
                        <at-progress
                            status="success"
                            :stroke-width="15"
                            :percent="getUserPercentage(user.time, project.time)"
                        />
                    </div>
                </div>

                <at-collapse simple accordion @on-change="handleCollapseTask(user, $event)">
                    <at-collapse-item v-for="task in user.tasks" :key="`tasks-${task.id}`" :name="`${task.id}`">
                        <div slot="title" class="row">
                            <div class="col-10 col-md-11 col-lg-12">
                                <span class="h4">{{ task.task_name }}</span>
                            </div>
                            <div class="col-4 col-md-3 col-lg-2">
                                <span class="h4">{{ formatDurationString(task.time) }}</span>
                            </div>
                            <div class="col-10">
                                <at-progress
                                    status="success"
                                    :stroke-width="15"
                                    :percent="getUserPercentage(task.time, user.time)"
                                />
                            </div>
                        </div>
                        <at-collapse class="project__screenshots screenshots" accordion @on-change="handleCollapseDate">
                            <span class="screenshots__title">{{ $t('field.screenshots') }}</span>
                            <at-collapse-item
                                v-for="(interval, key) of task.intervals"
                                :key="key"
                                :name="`${task.id}-${key}`"
                            >
                                <div slot="title" class="row">
                                    <div class="col-12">
                                        <span class="h5 screenshots__date">
                                            {{ moment(interval.date).locale($i18n.locale).format('MMMM DD, YYYY') }}
                                        </span>
                                    </div>
                                    <div class="col-12">
                                        <span class="h5">{{ formatDurationString(interval.time) }}</span>
                                    </div>
                                </div>

                                <template v-if="isDateOpened(`${task.id}-${key}`)">
                                    <template v-for="(hourScreens, idx) in interval.items">
                                        <div :key="`screen-${task.id}-${key}-${idx}`" class="row">
                                            <div
                                                v-for="(interval, index) in getHourRow(hourScreens)"
                                                :key="index"
                                                class="col-12 col-md-6 col-lg-4"
                                            >
                                                <Screenshot
                                                    v-if="interval"
                                                    :key="index"
                                                    class="screenshots__item"
                                                    :interval="interval"
                                                    :user="user"
                                                    :task="task"
                                                    :disableModal="true"
                                                    :showNavigation="true"
                                                    :showTask="false"
                                                    @click="onShow(interval.items, interval, user, task, project)"
                                                />

                                                <div
                                                    v-else
                                                    :key="index"
                                                    class="screenshots__item screenshots__placeholder"
                                                />
                                            </div>
                                        </div>
                                    </template>
                                </template>
                            </at-collapse-item>
                        </at-collapse>
                    </at-collapse-item>
                </at-collapse>
            </at-collapse-item>
        </at-collapse>

        <ScreenshotModal
            :show="modal.show"
            :interval="modal.interval"
            :project="modal.project"
            :task="modal.task"
            :user="modal.user"
            :showNavigation="true"
            :canRemove="false"
            @close="onHide"
            @showPrevious="onShowPrevious"
            @showNext="onShowNext"
        />
    </div>
</template>

<script>
    import moment from 'moment';
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import UserAvatar from '@/components/UserAvatar';
    import IntervalService from '@/services/resource/time-interval.service';
    import { formatDurationString } from '@/utils/time';

    const intervalService = new IntervalService();

    export default {
        name: 'ProjectLine',
        components: {
            Screenshot,
            ScreenshotModal,
            UserAvatar,
        },
        data() {
            return {
                modal: {
                    show: false,
                    intervals: {},
                    interval: null,
                    project: null,
                    user: null,
                    task: null,
                },
                openedDates: [],
                avatarSize: 35,
                taskDurations: {},
                screenshotsPerRow: 6,
            };
        },
        props: {
            project: {
                type: Object,
                required: true,
            },
            start: {
                type: String,
            },
            end: {
                type: String,
            },
        },
        mounted() {
            window.addEventListener('keydown', this.onKeyDown);
        },
        beforeDestroy() {
            window.removeEventListener('keydown', this.onKeyDown);
        },
        methods: {
            moment,
            formatDurationString,
            onShow(intervals, interval, user, task, project) {
                this.modal = {
                    ...this.modal,
                    show: true,
                    intervals,
                    interval,
                    user,
                    task,
                    project,
                };
            },
            onHide() {
                this.modal.show = false;
            },
            onKeyDown(e) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.onShowPrevious();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.onShowNext();
                }
            },
            onShowPrevious() {
                const currentIndex = this.modal.intervals.findIndex(i => +i.id === +this.modal.interval.id);
                if (currentIndex === -1 || currentIndex === 0) {
                    return;
                }

                this.modal = {
                    ...this.modal,
                    show: true,
                    interval: this.modal.intervals[currentIndex - 1],
                };
            },
            onShowNext() {
                const currentIndex = this.modal.intervals.findIndex(i => +i.id === +this.modal.interval.id);
                if (currentIndex === -1 || currentIndex === this.modal.intervals.length - 1) {
                    return;
                }

                this.modal = {
                    ...this.modal,
                    show: true,
                    interval: this.modal.intervals[currentIndex + 1],
                };
            },
            isDateOpened(collapseId) {
                return this.openedDates.findIndex(p => p === collapseId) > -1;
            },
            handleCollapseDate(data) {
                this.openedDates = data;
            },
            handleCollapseTask(user, taskID) {
                if (typeof taskID === 'object') {
                    taskID = taskID[0];
                }
                const key = `${user.id}:${taskID}`;
                this.$set(this.taskDurations, key, user.tasks);
            },
            formatDate(value) {
                return moment(value).format('DD.MM.YYYY HH:mm:ss');
            },
            getUserPercentage(seconds, totalTime) {
                if (!totalTime) {
                    return 0;
                }

                return Math.floor((seconds * 100) / totalTime);
            },
            getHourRow(screenshots) {
                return new Array(this.screenshotsPerRow).fill(null).map((el, i) => screenshots[i] || el);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .project {
        &__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
            padding: 14px 21px;
            border-bottom: 3px solid $blue-3;
        }

        &__title {
            color: $black-900;
            font-size: 2rem;
            font-weight: bold;
        }

        &__screenshots {
            margin-bottom: $spacing-05;
        }
    }

    .screenshots {
        padding-top: $spacing-03;

        &__title {
            font-size: 15px;
            color: $gray-3;
            font-weight: bold;
        }

        &__date {
            padding-left: 20px;
        }

        &__item {
            margin-bottom: $spacing-04;
        }

        &__placeholder {
            width: 100%;
            height: 150px;
            border: 2px dashed $gray-3;
        }

        &::v-deep {
            .at-collapse__header {
                padding: 14px 0;
            }

            img {
                object-fit: cover;
                height: 150px;
            }

            .at-collapse__icon {
                top: 20px;
                left: 0;

                color: black;
            }

            .at-collapse__icon.icon-chevron-right {
                display: block;
            }
        }
    }
</style>
