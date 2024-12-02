<template>
    <div class="canvas-wrapper">
        <div
            v-show="hoverPopup.show && !clickPopup.show"
            :style="{
                left: `${hoverPopup.x - 30}px`,
                bottom: `${hoverPopup.y}px`,
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
            :data-offset="`${clickPopup.borderX}px`"
            :style="{
                left: `${clickPopup.x - 30}px`,
                bottom: `${clickPopup.y}px`,
            }"
            class="popup"
        >
            <Screenshot
                v-if="clickPopup.event && screenshotsEnabled"
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
        <div ref="canvas" class="canvas" @pointerdown="onDown">
            <div ref="scrollbarTop" class="scrollbar-top" @scroll="onScroll">
                <div :style="{ width: `${totalWidth}px` }" />
            </div>
        </div>
    </div>
</template>

<script>
    import moment from 'moment-timezone';
    import { formatDurationString } from '@/utils/time';
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import IntervalService from '@/services/resource/time-interval.service';
    import { mapGetters } from 'vuex';
    import { SVG } from '@svgdotjs/svg.js';

    const titleHeight = 20;
    const subtitleHeight = 20;
    const timelineHeight = 80;
    const columns = 24;
    const minColumnWidth = 37;
    const popupWidth = 270;
    const canvasPadding = 20;
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
            ...mapGetters('screenshots', { screenshotsEnabled: 'enabled' }),
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
                scrollPos: 0,
                totalWidth: 0,
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
            canvasWidth() {
                return this.$refs.canvas.clientWidth;
            },
            columnWidth() {
                return Math.max(minColumnWidth, this.canvasWidth() / columns);
            },
            async contentWidth() {
                await this.$nextTick();
                this.totalWidth = columns * this.columnWidth();
                return this.totalWidth;
            },
            onDown(e) {
                this.$refs.canvas.addEventListener('pointermove', this.onMove);
                this.$refs.canvas.addEventListener('pointerup', this.onUp, { once: true });
                this.$refs.canvas.addEventListener('pointercancel', this.onCancel, { once: true });
            },
            async scrollCanvas(movementX, setScroll = true) {
                const canvas = this.$refs.canvas;
                const clientWidth = canvas.clientWidth;
                const entireWidth = await this.contentWidth();
                const height = this.height;
                const newScrollPos = this.scrollPos - movementX;
                if (newScrollPos <= 0) {
                    this.scrollPos = 0;
                } else if (newScrollPos >= entireWidth - clientWidth) {
                    this.scrollPos = entireWidth - clientWidth;
                } else {
                    this.scrollPos = newScrollPos;
                }
                setScroll ? await this.setScroll() : null;
                this.draw.viewbox(this.scrollPos, 0, clientWidth, height);
            },
            async onMove(e) {
                this.$refs.canvas.setPointerCapture(e.pointerId);
                await this.scrollCanvas(e.movementX);
            },
            onUp(e) {
                this.$refs.canvas.removeEventListener('pointermove', this.onMove);
            },
            onCancel(e) {
                this.$refs.canvas.removeEventListener('pointermove', this.onMove);
            },
            onScroll(e) {
                this.scrollCanvas(this.scrollPos - this.$refs.scrollbarTop.scrollLeft, false);
            },
            async setScroll(x = null) {
                await this.$nextTick();
                this.$refs.scrollbarTop.scrollLeft = x ?? this.scrollPos;
            },
            drawGrid: async function () {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();
                const draw = this.draw;
                // const width = draw.width();
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const columnWidth = this.columnWidth();
                draw.addTo(canvasContainer).size(width, this.height);
                this.draw.viewbox(0, 0, width, this.height);
                // Background
                this.draw
                    .rect(await this.contentWidth(), timelineHeight - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .fill('#fafafa')
                    .stroke({ color: '#dfe5ed', width: 1 })
                    .on('mousedown', () => this.$emit('outsideClick'));
                const maxLeftOffset = width - popupWidth + 2 * canvasPadding;
                const minLeftOffset = canvasPadding / 2;
                const clipPath = draw
                    .rect(await this.contentWidth(), timelineHeight - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .attr({
                        absolutePositioned: true,
                    });
                const squaresGroup = draw.group().clipWith(clipPath);
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
                        const line = draw
                            .line(0, 0, 0, timelineHeight)
                            .move(left, titleHeight + subtitleHeight)
                            .stroke({
                                color: '#dfe5ed',
                                width: 1,
                            });
                        squaresGroup.add(line);
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

                    const rectInterval = draw
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

                    rectInterval.on('mouseover', e => {
                        const popupY =
                            document.body.getBoundingClientRect().height - rectInterval.rbox().y + defaultCornerOffset;
                        const canvasRight = this.$refs.canvas.getBoundingClientRect().right;
                        const rectMiddleX = rectInterval.rbox().cx;
                        const minLeft = this.$refs.canvas.getBoundingClientRect().left;
                        const left =
                            rectMiddleX > canvasRight
                                ? canvasRight - defaultCornerOffset / 2
                                : rectMiddleX < minLeft
                                  ? minLeft
                                  : rectMiddleX;
                        const maxRight = canvasRight - popupWidth + 2 * canvasPadding;
                        const popupX =
                            left > maxRight ? maxRight : left <= minLeft ? minLeft + defaultCornerOffset : left;
                        const arrowX = defaultCornerOffset + left - popupX;
                        this.hoverPopup = {
                            show: true,
                            x: popupX,
                            y: popupY,
                            event,
                            borderX: arrowX,
                        };
                    });

                    rectInterval.on('mouseout', e => {
                        this.hoverPopup = {
                            ...this.hoverPopup,
                            show: false,
                        };
                    });

                    rectInterval.on('mousedown', e => {
                        this.$emit('selectedIntervals', event);
                        const { left: canvasLeft, right: canvasRight } = this.$refs.canvas.getBoundingClientRect();
                        const rectBox = rectInterval.rbox();
                        const popupY =
                            document.body.getBoundingClientRect().height - rectInterval.rbox().y + defaultCornerOffset;
                        const rectMiddleX = rectBox.cx;

                        // Determine initial left position within canvas bounds
                        const left =
                            rectMiddleX > canvasRight
                                ? canvasRight - defaultCornerOffset / 2
                                : Math.max(rectMiddleX, canvasLeft);

                        // Calculate maximum allowed position for popup's left
                        const maxRight = canvasRight - popupWidth + 2 * canvasPadding;
                        const popupX = left > maxRight ? maxRight : Math.max(left, canvasLeft + defaultCornerOffset);

                        // Calculate the position for the arrow in the popup
                        const arrowX = defaultCornerOffset + left - popupX;

                        this.clickPopup = {
                            show: true,
                            x: popupX,
                            y: popupY,
                            event,
                            borderX: arrowX,
                        };
                        e.stopPropagation();
                    });

                    draw.add(rectInterval);
                });
            },
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
            onResize: function () {
                this.drawGrid();
            },
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
    .canvas {
        position: relative;
        user-select: none;
        touch-action: pan-y;
        cursor: move;
        &::v-deep canvas {
            box-sizing: content-box;
        }
    }
    .scrollbar-top {
        position: absolute;
        left: 0;
        top: -1rem;
        width: 100%;
        height: 10px;
        overflow-x: auto;
    }
    .scrollbar-top {
        & > div {
            height: 1px;
        }

        &::-webkit-scrollbar {
            height: 7px;
        }

        &::-webkit-scrollbar-track {
            background: transparent;
        }

        &::-webkit-scrollbar-button {
            display: none;
        }

        &::-webkit-scrollbar-thumb {
            background: #2e2ef9;
            border-radius: 3px;
        }
    }
    @media (max-width: 1110px) {
        .scrollbar-top {
            top: -0.5rem;
        }
    }
</style>
