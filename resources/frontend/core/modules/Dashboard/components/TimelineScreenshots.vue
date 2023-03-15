<template>
    <div class="screenshots">
        <h3 class="screenshots__title">{{ $t('field.screenshots') }}</h3>
        <at-checkbox-group v-model="selectedIntervals">
            <div class="row">
                <div
                    v-for="(interval, index) in intervals[this.user.id]"
                    :key="interval.id"
                    class="col-4 col-xl-3 screenshots__item"
                >
                    <div class="screenshot" :index="index" @click.shift.prevent.stop="onShiftClick(index)">
                        <Screenshot
                            :disableModal="true"
                            :project="{ id: interval.project_id, name: interval.project_name }"
                            :interval="interval"
                            :task="interval.task"
                            :user="user"
                            :timezone="timezone"
                            @click="showPopup(interval, $event)"
                        />
                        <div @click="onCheckboxClick(index)">
                            <at-checkbox class="screenshot__checkbox" :label="interval.id" />
                        </div>
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
                    @remove="onRemove"
                    @showNext="showNext"
                    @showPrevious="showPrevious"
                />
            </div>
        </at-checkbox-group>
    </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import TimeIntervalService from '@/services/resource/time-interval.service';

    export default {
        name: 'TimelineScreenshots',
        components: {
            Screenshot,
            ScreenshotModal,
        },
        data() {
            return {
                intervalsService: new TimeIntervalService(),
                selectedIntervals: [],
                modal: {
                    interval: null,
                    project: null,
                    task: null,
                    show: false,
                    user: null,
                },
                firstSelectedCheckboxIndex: null,
            };
        },
        computed: {
            ...mapGetters('dashboard', ['tasks', 'intervals', 'timezone']),
            ...mapGetters('user', ['user']),
            projects() {
                return Object.keys(this.tasks)
                    .map(taskID => this.tasks[taskID])
                    .reduce((projects, task) => ({ ...projects, [task.project_id]: task.project }), {});
            },
        },
        mounted() {
            window.addEventListener('keydown', this.onKeyDown);
        },
        beforeDestroy() {
            window.removeEventListener('keydown', this.onKeyDown);
        },
        methods: {
            onShiftClick(index) {
                if (this.firstSelectedCheckboxIndex === null) {
                    this.firstSelectedCheckboxIndex = index;
                }

                this.selectedIntervals = this.intervals[this.user.id]
                    .slice(
                        Math.min(index, this.firstSelectedCheckboxIndex),
                        Math.max(index, this.firstSelectedCheckboxIndex) + 1,
                    )
                    .map(i => i.id);
            },
            onCheckboxClick(index) {
                if (this.firstSelectedCheckboxIndex === null) {
                    this.firstSelectedCheckboxIndex = index;
                }
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
            showPopup(interval, e) {
                if (e.shiftKey) {
                    return;
                }

                if (typeof interval !== 'object' || interval.id === null) {
                    return;
                }

                this.modal = {
                    show: true,
                    project: { id: interval.project_id, name: interval.project_name },
                    user: interval,
                    task: { id: interval.task_id, task_name: interval.task_name },
                    interval,
                };
            },
            onHide() {
                this.modal.show = false;
            },
            showPrevious() {
                const intervals = this.intervals[this.modal.user.user_id];

                const currentIndex = intervals.findIndex(x => x.id === this.modal.interval.id);

                if (currentIndex > 0) {
                    const interval = intervals[currentIndex - 1];
                    if (interval) {
                        this.modal.interval = interval;
                        this.modal.user = interval;
                        this.modal.project = { id: interval.project_id, name: interval.project_name };
                        this.modal.task = { id: interval.task_id, name: interval.task_name };
                    }
                }
            },
            showNext() {
                const intervals = this.intervals[this.modal.user.user_id];

                const currentIndex = intervals.findIndex(x => x.id === this.modal.interval.id);

                if (currentIndex < intervals.length - 1) {
                    const interval = intervals[currentIndex + 1];
                    if (interval) {
                        this.modal.interval = interval;
                        this.modal.user = interval;
                        this.modal.project = { id: interval.project_id, name: interval.project_name };
                        this.modal.task = { id: interval.task_id, name: interval.task_name };
                    }
                }
            },
            async onRemove(intervalID) {
                try {
                    await this.intervalsService.deleteItem(intervalID);

                    this.$emit('on-remove', [this.modal.interval]);

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.screenshot.delete.success.title'),
                        message: this.$t('notification.screenshot.delete.success.message'),
                    });

                    this.modal.show = false;
                } catch (e) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.screenshot.delete.error.title'),
                        message: this.$t('notification.screenshot.delete.error.message'),
                    });
                }
            },
            clearSelectedIntervals() {
                this.selectedIntervals = [];
            },
        },
        watch: {
            selectedIntervals(intervalIds) {
                if (intervalIds.length === 0) {
                    this.firstSelectedCheckboxIndex = null;
                }

                this.$emit(
                    'onSelectedIntervals',
                    this.intervals[this.user.id].filter(i => intervalIds.indexOf(i.id) !== -1),
                );
            },
        },
    };
</script>

<style lang="scss" scoped>
    .screenshots {
        &__title {
            color: #b1b1be;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 16px;
            margin-top: 37px;
        }

        &__item {
            margin-bottom: $layout-01;
        }
    }

    .screenshot {
        position: relative;
        margin-bottom: $layout-01;

        &__checkbox {
            left: -5px;
            position: absolute;
            top: -5px;
            z-index: 0;
        }

        &::v-deep {
            .screenshot__image {
                img {
                    height: 100px;
                }
            }
        }
    }
</style>
