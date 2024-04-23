<template>
    <div ref="canvas" class="canvas">
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
    import throttle from 'lodash/throttle';
    import moment from 'moment-timezone';
    import { formatDurationString } from '@/utils/time';
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import IntervalService from '@/services/resource/time-interval.service';
    import { mapGetters } from 'vuex';
    import { SVG } from '@svgdotjs/svg.js';

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
            this.draw = SVG();
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
            drawGrid: throttle(function () {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();
                const draw = this.draw;
                // const width = draw.width();
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const columnWidth = width / columns;
                draw.addTo(canvasContainer).size(width, '100%');
                // Background
                this.draw
                    .rect(width - 1, timelineHeight - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .fill('#fafafa')
                    .stroke({ color: '#dfe5ed', width: 1 })
                    .on('mousedown', () => this.$emit('outsideClick'));
                const maxLeftOffset = width - popupWidth + 2 * canvasPadding;
                const minLeftOffset = canvasPadding / 2;

                for (let i = 0; i < columns; ++i) {
                    const date = moment().startOf('day').add(i, 'hours');
                    const left = columnWidth * i;

                    // Column header - hour
                    draw.text(date.format('h'))
                        .move(left + columnWidth / 2, 0)
                        .addClass('text-center')
                        .width(columnWidth)
                        .height(titleHeight)
                        .attr({
                            'text-anchor': 'middle',
                            'font-family': 'Nunito, sans-serif',
                            'font-size': 15,
                            fill: '#151941',
                        });

                    // Column header - am/pm
                    draw.text(function (add) {
                        add.tspan(date.format('A')).newLine();
                    })
                        .move(left + columnWidth / 2, titleHeight - 5)
                        .attr({
                            'text-anchor': 'middle',
                            'font-family': 'Nunito, sans-serif',
                            'font-size': 10,
                            'font-weight': 600,
                            fill: '#b1b1be',
                        });

                    // Vertical grid line
                    if (i > 0) {
                        draw.line(0, 0, 0, timelineHeight)
                            .move(left, titleHeight + subtitleHeight)
                            .stroke({
                                color: '#dfe5ed',
                                width: 1,
                            });
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

                    const rect = draw
                        .rect(width, 30)
                        .move(Math.floor(leftOffset * columnWidth), titleHeight + subtitleHeight + 22)
                        .radius(3)
                        .attr({
                            'text-anchor': 'inherit',
                            'font-family': 'Nunito, sans-serif',
                            'font-size': 15,
                            'font-weight': 600,
                            fill: event.is_manual == '1' ? '#c4b52d' : '#2dc48d',
                        })
                        .stroke({
                            color: 'transparent',
                            width: 0,
                        })
                        .css({
                            cursor: 'pointer',
                            'pointer-events': 'auto',
                        })
                        .width(width)
                        .height(30);

                    rect.on('mouseover', e => {
                        if (e.target.attributes.x.value > maxLeftOffset) {
                            this.hoverPopup = {
                                show: true,
                                x: maxLeftOffset,
                                y: e.target.attributes.y.value,
                                event,
                                borderX: defaultCornerOffset + e.target.attributes.x.value - maxLeftOffset,
                            };
                        } else {
                            this.hoverPopup = {
                                show: true,
                                x: e.target.attributes.x < minLeftOffset ? minLeftOffset : e.target.attributes.x.value,
                                y: e.target.attributes.y.value - 38,
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

                        if (e.target.attributes.x.value > maxLeftOffset) {
                            this.clickPopup = {
                                show: true,
                                x: maxLeftOffset,
                                y: e.target.attributes.y.value - 38,
                                event,
                                borderX: defaultCornerOffset + e.target.attributes.x.value - maxLeftOffset,
                            };
                        } else {
                            this.clickPopup = {
                                show: true,
                                x: e.target.attributes.x.value,
                                y: e.target.attributes.y.value - 38,
                                event,
                                borderX: defaultCornerOffset,
                            };
                        }

                        e.stopPropagation();
                    });

                    draw.add(rect);
                });
            }),
            onClick(e) {
                if (
                    (e.target &&
                        e.target.parentElement &&
                        !e.target.parentElement.classList.contains(this.draw.node.classList) &&
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
                this.drawGrid();
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
                this.drawGrid();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .canvas {
        position: relative;
        user-select: none;
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
