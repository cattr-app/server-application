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
            <div v-if="hoverPopup.event">
                {{ hoverPopup.event.task_name }}
                ({{ hoverPopup.event.project_name }})
            </div>

            <div v-if="hoverPopup.event">
                {{ formatDuration(hoverPopup.event.duration) }}
            </div>

            <a :style="{ left: `${hoverPopup.borderX}px` }" class="corner"></a>
        </div>

        <div
            v-show="clickPopup.show"
            :style="{
                left: `${clickPopup.x - 30}px`,
                bottom: `${height - clickPopup.y + 10}px`,
            }"
            class="popup"
        >
            <template v-if="clickPopup.event">
                <div>
                    <Screenshot
                        :disableModal="true"
                        :lazyImage="false"
                        :project="{ id: clickPopup.event.project_id, name: clickPopup.event.project_name }"
                        :interval="clickPopup.event"
                        :showText="false"
                        :task="{ id: clickPopup.event.task_id, name: clickPopup.event.task_name }"
                        :user="clickPopup.event"
                        @click="showPopup"
                    />
                </div>

                <div>
                    <router-link :to="`/tasks/view/${clickPopup.event.task_id}`">
                        {{ clickPopup.event.task_name }}
                    </router-link>

                    <router-link :to="`/projects/view/${clickPopup.event.project_id}`">
                        ({{ clickPopup.event.project_name }})
                    </router-link>
                </div>
            </template>

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
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import IntervalService from '@/services/resource/time-interval.service';
    import { formatDurationString } from '@/utils/time';
    import { fabric } from 'fabric';
    import throttle from 'lodash/throttle';
    import moment from 'moment-timezone';
    import { mapGetters } from 'vuex';

    let intervalService = new IntervalService();

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
    const rowHeight = 65;
    const columns = 24;

    const popupWidth = 270;
    const canvasPadding = 24;
    const defaultCornerOffset = 15;

    export default {
        name: 'TeamDayGraph',
        components: {
            Screenshot,
            ScreenshotModal,
        },
        props: {
            users: {
                type: Array,
                required: true,
            },
            start: {
                type: String,
                required: true,
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
                    intervalID: null,
                    borderX: 0,
                },
                modal: {
                    show: false,
                    project: null,
                    task: null,
                    user: null,
                    interval: null,
                },
            };
        },
        computed: {
            ...mapGetters('dashboard', ['intervals', 'timezone']),
            ...mapGetters('user', ['companyData']),
            height() {
                return this.users.length * rowHeight + titleHeight + subtitleHeight;
            },
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
                    task: { id: this.clickPopup.event.task_id, task_name: this.clickPopup.event.task_name },
                    interval: this.clickPopup.event,
                };
            },
            onHide() {
                this.modal = {
                    ...this.modal,
                    show: false,
                };

                this.$emit('selectedIntervals', null);
            },
            onKeyDown(e) {
                if (!this.modal.show) {
                    return;
                }

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
                const height = this.users.length * rowHeight;
                const columnWidth = width / columns;

                // Background
                this.canvas.add(
                    new fabric.Rect({
                        left: 0,
                        top: titleHeight + subtitleHeight,
                        width: width - 1,
                        height: height - 1,
                        rx: 20,
                        ry: 20,
                        fill: '#FAFAFA',
                        stroke: '#DFE5ED',
                        strokeWidth: 1,
                        ...fabricObjectOptions,
                    }).on('mousedown', () => this.$emit('outsideClick')),
                );

                for (let column = 0; column < columns; ++column) {
                    const date = moment().startOf('day').add(column, 'hours');
                    const left = columnWidth * column;

                    // Column headers - hours
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

                    // Column headers - am/pm
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
                            fill: '#B1B1BE',
                            ...fabricObjectOptions,
                        }),
                    );

                    // Vertical grid lines
                    if (column > 0) {
                        this.canvas.add(
                            new fabric.Line([0, 0, 0, height], {
                                left,
                                top: titleHeight + subtitleHeight,
                                stroke: '#DFE5ED',
                                strokeWidth: 1,
                                ...fabricObjectOptions,
                            }),
                        );
                    }
                }

                if (!this.$refs.canvasWrapper) {
                    return;
                }

                const { width: canvasWidth } = this.$refs.canvasWrapper.getBoundingClientRect();
                const maxLeftOffset = canvasWidth - popupWidth + 2 * canvasPadding;
                const minLeftOffset = canvasPadding / 2;

                this.users.forEach((user, row) => {
                    const top = row * rowHeight + titleHeight + subtitleHeight;

                    // Horizontal grid lines
                    if (row > 0) {
                        this.canvas.add(
                            new fabric.Line([0, 0, width, 0], {
                                left: 0,
                                top,
                                stroke: '#DFE5ED',
                                strokeWidth: 1,
                                ...fabricObjectOptions,
                            }),
                        );
                    }

                    // Intervals
                    if (Object.prototype.hasOwnProperty.call(this.intervals, user.id)) {
                        this.intervals[user.id].forEach(event => {
                            const leftOffset =
                                moment
                                    .tz(event.start_at, this.companyData.timezone)
                                    .tz(this.timezone)
                                    .diff(moment.tz(this.start, this.timezone).startOf('day'), 'hours', true) % 24;

                            const width = ((Math.max(event.duration, 60) + 120) * columnWidth) / 60 / 60;

                            const rect = new fabric.Rect({
                                left: Math.floor(leftOffset * columnWidth),
                                top: top + rowHeight / 4,
                                width,
                                height: rowHeight / 2,
                                rx: 2,
                                ry: 2,
                                fill: event.is_manual == '1' ? '#c4b52d' : '#2DC48D',
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
                    }
                });

                this.canvas.requestRenderAll();
            }, 100),
            onResize: throttle(function () {
                if (!this.$refs.canvasWrapper) {
                    return;
                }
                const { width } = this.$refs.canvasWrapper.getBoundingClientRect();
                this.canvas.setWidth(width);
                this.canvas.setHeight(this.height);
                this.draw();
            }, 200),
            onClick(e) {
                if (e.button !== 0 || (e.target && e.target.closest('.popup'))) {
                    return;
                }

                this.clickPopup = {
                    ...this.clickPopup,
                    show: false,
                };
            },
            async onRemove() {
                try {
                    await intervalService.deleteItem(this.modal.interval.id);

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.screenshot.delete.success.title'),
                        message: this.$t('notification.screenshot.delete.success.message'),
                    });
                    this.onHide();
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
            users() {
                this.onResize();
            },
            intervals() {
                this.draw();
            },
            timezone() {
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
            background: #ffffff;
            border: 0;

            border-radius: 20px;

            box-shadow: 0px 7px 64px rgba(0, 0, 0, 0.07);
            display: block;

            padding: 10px;

            position: absolute;

            text-align: center;

            width: 270px;

            z-index: 3;

            & .corner {
                border-left: 15px solid transparent;

                border-right: 15px solid transparent;
                border-top: 10px solid #ffffff;

                bottom: -10px;
                content: ' ';
                display: block;

                height: 0;
                left: 15px;

                position: absolute;
                width: 0;

                z-index: 1;
            }
        }
    }
</style>
