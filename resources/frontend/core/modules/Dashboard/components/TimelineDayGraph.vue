<template>
    <div ref="canvasWrapper" class="canvas">
        <canvas ref="canvas" />

        <div
            v-show="hoverPopup.show && !clickPopup.show"
            :style="{
                left: `${hoverPopup.x - 30}px`,
                bottom: `${height - hoverPopup.y + 10}px`,
            }"
            class="popup"
        >
            <template v-if="hoverPopup.event">
                <div>
                    {{ hoverPopup.event.task_name }}
                    ({{ hoverPopup.event.project_name }})
                </div>

                <div>
                    {{ formatDuration(hoverPopup.event.duration) }}
                </div>
            </template>

            <a :style="{ left: `${hoverPopup.borderX}px` }" class="corner" />
        </div>

        <div
            v-show="clickPopup.show"
            :data-offset="`${clickPopup.borderX}px`"
            :style="{
                left: `${clickPopup.x - 30}px`,
                bottom: `${height - clickPopup.y + 10}px`,
            }"
            class="popup"
        >
            <Screenshot
                v-if="clickPopup.event"
                :disableModal="true"
                :lazyImage="false"
                :project="{ id: clickPopup.event.project_id, name: clickPopup.event.project_name }"
                :interval="clickPopup.event"
                :showText="false"
                :task="{ id: clickPopup.event.task_id, name: clickPopup.event.task_name }"
                :user="clickPopup.event"
                @click="showPopup"
            />

            <div v-if="clickPopup.event">
                <router-link :to="`/tasks/view/${clickPopup.event.task_id}`">
                    {{ clickPopup.event.task_name }}
                </router-link>

                <router-link :to="`/projects/view/${clickPopup.event.project_id}`">
                    ({{ clickPopup.event.project_name }})
                </router-link>
            </div>

            <a :style="{ left: `${clickPopup.borderX}px` }" class="corner" />
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
</template>

<script>
    import { fabric } from 'fabric';
    import throttle from 'lodash/throttle';
    import moment from 'moment-timezone';
    import { formatDurationString } from '@/utils/time';
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import IntervalService from '@/services/resource/time-interval.service';
    import { mapGetters } from 'vuex';

    const fabricObjectOptions = {
        editable: false,
        selectable: false,
        objectCaching: false,
        hasBorders: false,
        hasControls: false,
        hasRotatingPoint: false,
        cursor: 'default',
        hoverCursor: 'default',
    };

    const titleHeight = 20;
    const subtitleHeight = 20;
    const timelineHeight = 80;
    const columns = 24;

    const popupWidth = 270;
    const canvasPadding = 24;
    const defaultCornerOffset = 15;

    export default {
        name: 'TimelineDayGraph',
        props: {
            start: {
                type: String,
                required: true,
            },
            end: {
                type: String,
                required: true,
            },
            events: {
                type: Array,
                required: true,
            },
            timezone: {
                type: String,
                required: true,
            },
        },
        components: {
            Screenshot,
            ScreenshotModal,
        },
        computed: {
            ...mapGetters('dashboard', ['tasks', 'intervals']),
            ...mapGetters('user', ['user', 'companyData']),
            height() {
                return timelineHeight + titleHeight + subtitleHeight;
            },
            tasks() {
                if (!this.user) {
                    return {};
                }

                const userIntervals = this.intervals[this.user.id];
                if (!userIntervals) {
                    return {};
                }

                return userIntervals.intervals
                    .map(interval => interval.task)
                    .reduce((obj, task) => ({ ...obj, [task.id]: task }), {});
            },
            projects() {
                return Object.keys(this.tasks)
                    .map(taskID => this.tasks[taskID])
                    .reduce((projects, task) => ({ ...projects, [task.project_id]: task.project }), {});
            },
        },
        data() {
            return {
                hoverPopup: {
                    show: false,
                    x: 0,
                    y: 0,
                    event: null,
                    borderX: 0,
                },
                clickPopup: {
                    show: false,
                    x: 0,
                    y: 0,
                    event: null,
                    borderX: 0,
                },
                intervalService: new IntervalService(),
                modal: {
                    interval: null,
                    project: null,
                    task: null,
                    show: false,
                },
            };
        },
        mounted() {
            this.canvas = new fabric.Canvas(this.$refs.canvas, {
                backgroundColor: '#fff',
                renderOnAddRemove: false,
                selection: false,
                skipOffscreen: true,
            });

            this.onResize();
            window.addEventListener('resize', this.onResize);
            window.addEventListener('mousedown', this.onClick);
            window.addEventListener('keydown', this.onKeyDown);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
            window.removeEventListener('mousedown', this.onClick);
            window.removeEventListener('keydown', this.onKeyDown);
        },
        methods: {
            formatDuration: formatDurationString,
            showPopup() {
                this.modal = {
                    show: true,
                    project: { id: this.clickPopup.event.project_id, name: this.clickPopup.event.project_name },
                    user: this.clickPopup.event,
                    task: { id: this.clickPopup.event.task_id, name: this.clickPopup.event.task_name },
                    interval: this.clickPopup.event,
                };
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
            draw: throttle(function () {
                this.canvas.clear();

                const width = this.canvas.getWidth();
                const columnWidth = width / columns;

                // Background
                this.canvas.add(
                    new fabric.Rect({
                        left: 0,
                        top: titleHeight + subtitleHeight,
                        width: width - 1,
                        height: timelineHeight - 1,
                        rx: 20,
                        ry: 20,
                        fill: '#fafafa',
                        stroke: '#dfe5ed',
                        strokeWidth: 1,
                        ...fabricObjectOptions,
                    }).on('mousedown', () => this.$emit('outsideClick')),
                );

                if (!this.$refs.canvasWrapper) {
                    return;
                }
                const { width: canvasWidth } = this.$refs.canvasWrapper.getBoundingClientRect();
                const maxLeftOffset = canvasWidth - popupWidth + 2 * canvasPadding;
                const minLeftOffset = canvasPadding / 2;

                for (let i = 0; i < columns; ++i) {
                    const date = moment().startOf('day').add(i, 'hours');
                    const left = columnWidth * i;

                    // Column header - hour
                    this.canvas.add(
                        new fabric.Textbox(date.format('h'), {
                            left,
                            top: 0,
                            width: columnWidth,
                            height: titleHeight,
                            textAlign: 'center',
                            fontFamily: 'Nunito, sans-serif',
                            fontSize: 15,
                            fill: '#151941',
                            ...fabricObjectOptions,
                        }),
                    );

                    // Column header - am/pm
                    this.canvas.add(
                        new fabric.Textbox(date.format('A'), {
                            left,
                            top: titleHeight,
                            width: columnWidth,
                            height: subtitleHeight,
                            textAlign: 'center',
                            fontFamily: 'Nunito, sans-serif',
                            fontSize: 10,
                            fontWeight: '600',
                            fill: '#b1b1be',
                            ...fabricObjectOptions,
                        }),
                    );

                    // Vertical grid line
                    if (i > 0) {
                        this.canvas.add(
                            new fabric.Line([0, 0, 0, timelineHeight], {
                                left,
                                top: titleHeight + subtitleHeight,
                                stroke: '#dfe5ed',
                                strokeWidth: 1,
                                ...fabricObjectOptions,
                            }),
                        );
                    }
                }

                // Intervals
                this.events.forEach(event => {
                    const leftOffset =
                        moment
                            .tz(event.start_at, this.companyData.timezone)
                            .tz(this.timezone)
                            .diff(moment.tz(this.start, this.timezone).startOf('day'), 'hours', true) % 24;

                    const width = ((Math.max(event.duration, 60) + 120) * columnWidth) / 60 / 60;

                    const rect = new fabric.Rect({
                        left: Math.floor(leftOffset * columnWidth),
                        top: titleHeight + subtitleHeight + 22,
                        width,
                        height: 30,
                        rx: 3,
                        ry: 3,
                        fill: event.is_manual == '1' ? '#c4b52d' : '#2dc48d',
                        stroke: 'transparent',
                        strokeWidth: 0,
                        ...fabricObjectOptions,
                        cursor: 'pointer',
                        hoverCursor: 'pointer',
                    });

                    rect.on('mouseover', e => {
                        if (e.target.left > maxLeftOffset) {
                            this.hoverPopup = {
                                show: true,
                                x: maxLeftOffset,
                                y: e.target.top,
                                event,
                                borderX: defaultCornerOffset + e.target.left - maxLeftOffset,
                            };
                        } else {
                            this.hoverPopup = {
                                show: true,
                                x: e.target.left < minLeftOffset ? minLeftOffset : e.target.left,
                                y: e.target.top,
                                borderX: defaultCornerOffset,
                                event,
                            };
                        }
                    });

                    rect.on('mouseout', e => {
                        this.hoverPopup = {
                            ...this.hoverPopup,
                            show: false,
                        };
                    });

                    rect.on('mousedown', e => {
                        this.$emit('selectedIntervals', event);

                        if (e.target.left > maxLeftOffset) {
                            this.clickPopup = {
                                show: true,
                                x: maxLeftOffset,
                                y: e.target.top,
                                event,
                                borderX: defaultCornerOffset + e.target.left - maxLeftOffset,
                            };
                        } else {
                            this.clickPopup = {
                                show: true,
                                x: e.target.left,
                                y: e.target.top,
                                event,
                                borderX: defaultCornerOffset,
                            };
                        }

                        e.e.stopPropagation();
                    });

                    this.canvas.add(rect);
                });

                this.canvas.requestRenderAll();
            }),
            onClick(e) {
                if (
                    (e.target &&
                        e.target.parentElement &&
                        !e.target.parentElement.classList.contains(this.canvas.wrapperEl.classList) &&
                        !e.target.closest('.time-interval-edit-panel') &&
                        !e.target.closest('.screenshot') &&
                        !e.target.closest('.modal') &&
                        !e.target.closest('.at-modal') &&
                        !e.target.closest('.popup')) ||
                    (e.target.closest('.time-interval-edit-panel') &&
                        e.target.closest('.time-interval-edit-panel__btn') &&
                        e.target.closest('.at-btn--error')) ||
                    (e.target.closest('.modal') && e.target.closest('.modal-remove'))
                ) {
                    if (this.clickPopup.show) {
                        this.clickPopup.show = false;
                    }
                }
            },
            onResize: throttle(function () {
                if (!this.$refs.canvasWrapper) {
                    return;
                }
                const { width } = this.$refs.canvasWrapper.getBoundingClientRect();
                this.canvas.setWidth(width);

                const height = titleHeight + subtitleHeight + timelineHeight;
                this.canvas.setHeight(height);

                this.draw();
            }, 100),
            async onRemove() {
                try {
                    await this.intervalService.deleteItem(this.modal.interval.id);

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.screenshot.delete.success.title'),
                        message: this.$t('notification.screenshot.delete.success.message'),
                    });
                    this.onHide();
                    this.$emit('selectedIntervals', null);
                    this.$emit('remove', [this.modal.interval]);
                } catch (e) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.screenshot.delete.error.title'),
                        message: this.$t('notification.screenshot.delete.error.message'),
                    });
                }
            },
        },
        watch: {
            events() {
                this.draw();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .canvas {
        position: relative;

        &::v-deep canvas {
            box-sizing: content-box;
        }

        .popup {
            background: #fff;
            border: 0;

            border-radius: 20px;

            box-shadow: 0px 7px 64px rgba(0, 0, 0, 0.07);
            display: block;

            padding: 10px;

            position: absolute;

            text-align: center;

            width: 270px;

            z-index: 1;

            & .corner {
                border-left: 15px solid transparent;

                border-right: 15px solid transparent;
                border-top: 10px solid #fff;

                bottom: -10px;
                content: ' ';
                display: block;

                height: 0;

                position: absolute;
                width: 0;

                z-index: 1;
            }
        }
    }
</style>
