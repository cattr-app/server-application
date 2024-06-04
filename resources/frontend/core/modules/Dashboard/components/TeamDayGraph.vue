<template>
    <div ref="canvas" class="canvas">
        <div
            v-show="hoverPopup.show && !clickPopup.show"
            :style="{
                left: `${hoverPopup.x - 30}px`,
                bottom: `${height() - hoverPopup.y + 50}px`,
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
                bottom: `${height() - clickPopup.y + 10}px`,
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
        <div class="scroll-area-wrapper">
            <div ref="scrollArea" class="scroll-area"></div>
        </div>
        <div ref="scrollbarTop" class="scrollbar-top" @scroll="onScroll">
            <div :style="{ width: `${contentWidth()}px` }" />
        </div>
    </div>
</template>

<script>
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import IntervalService from '@/services/resource/time-interval.service';
    import { formatDurationString } from '@/utils/time';
    import throttle from 'lodash/throttle';
    import moment from 'moment-timezone';
    import { mapGetters } from 'vuex';
    import { SVG } from '@svgdotjs/svg.js';

    let intervalService = new IntervalService();

    const titleHeight = 20;
    const subtitleHeight = 20;
    const rowHeight = 64;
    const columns = 24;
    const minColumnWidth = 85;
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
                lastPosX: 0,
                offsetX: 0,
            };
        },
        computed: {
            ...mapGetters('dashboard', ['intervals', 'timezone']),
            ...mapGetters('user', ['companyData']),
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
            document.removeEventListener('pointermove', this.onMove);
            document.removeEventListener('pointerup', this.onUp);
            document.removeEventListener('pointercancel', this.onUp);
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
            height() {
                return this.users.length * rowHeight;
            },
            canvasWidth() {
                if (!this.$refs.canvas) {
                    return 500;
                }
                return this.$refs.canvas.clientWidth;
            },
            columnWidth() {
                return Math.max(minColumnWidth, this.canvasWidth() / columns);
            },
            contentWidth() {
                return columns * this.columnWidth();
            },
            onDown(e) {
                if (e.buttons & 1) {
                    this.draw.selection = false;
                    this.lastPosX = e.clientX;
                    document.addEventListener('pointermove', this.onMove);
                    document.addEventListener('pointerup', this.onUp);
                    document.addEventListener('pointercancel', this.onUp);
                }
            },

            maxScrollX() {
                return this.contentWidth() - this.canvasWidth();
            },
            onMove(e) {
                if (e.buttons & 1) {
                    const deltaX = e.clientX - this.lastPosX;
                    this.offsetX -= deltaX;
                    this.offsetX = Math.min(this.maxScrollX(), Math.max(this.offsetX, 0));
                    this.lastPosX = e.clientX;
                    this.setScroll(this.offsetX);
                }
            },
            onUp(e) {
                document.removeEventListener('pointermove', this.onMove);
                document.removeEventListener('pointerup', this.onUp);
                document.removeEventListener('pointercancel', this.onUp);
            },
            onScroll(e) {
                this.setScroll(e.target.scrollLeft);
            },
            setScroll(x) {
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const height = canvasContainer.clientHeight;
                this.$refs.scrollbarTop.scrollLeft = x;
                this.draw.viewbox(x, -3, width, height);
            },
            resetScroll() {
                this.setScroll(0);
            },
            drawGrid: throttle(function () {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const height = this.users.length * rowHeight;
                const columnWidth = width / columns;
                const draw = this.draw;
                draw.addTo(canvasContainer).size(width, height + titleHeight + subtitleHeight);
                if (height <= 0) {
                    return;
                }
                this.draw.viewbox(0, 20, width, height);
                // Background
                const rectBackground = draw
                    .rect(this.contentWidth(), height - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .fill('#FAFAFA')
                    .stroke({ color: '#DFE5ED', width: 1 })
                    .on('mousedown', () => this.$emit('outsideClick'));
                rectBackground.on('mousedown', e => {
                    this.onDown(e);
                    e.stopPropagation();
                });
                rectBackground.on('scroll', e => {
                    this.onScroll(e);
                    e.stopPropagation();
                });
                draw.add(rectBackground);
                for (let column = 0; column < columns; ++column) {
                    const date = moment().startOf('day').add(column, 'hours');
                    let left = this.columnWidth() * column;
                    // Column headers - hours
                    draw.text(date.format('h'))
                        .move(left + columnWidth / 2 + 15, 0)
                        .size(columnWidth, titleHeight)
                        .attr({
                            'text-anchor': 'middle',
                            'font-family': 'Nunito, sans-serif',
                            'font-size': 15,
                            fill: '#151941',
                        });
                    // Column headers - am/pm
                    draw.text(date.format('A'))
                        .move(left + columnWidth / 2 + 15, titleHeight - 5)
                        .size(columnWidth, subtitleHeight)
                        .attr({
                            'text-anchor': 'middle',
                            'font-family': 'Nunito, sans-serif',
                            'font-size': 10,
                            'font-weight': '600',
                            fill: '#B1B1BE',
                        });

                    // // Vertical grid lines
                    if (column > 0) {
                        draw.line(0, titleHeight + subtitleHeight, 0, height + titleHeight + subtitleHeight)
                            .move(left, titleHeight + subtitleHeight)
                            .stroke({ color: '#DFE5ED', width: 1 });
                    }
                }

                const maxLeftOffset = width - popupWidth + 2 * canvasPadding;
                const clipPath = draw
                    .rect(this.contentWidth(), height - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .attr({
                        absolutePositioned: true,
                    });
                const squaresGroup = draw.group().clipWith(clipPath);
                this.users.forEach((user, row) => {
                    const top = row * rowHeight + titleHeight + subtitleHeight;

                    // Horizontal grid lines
                    if (row > 0) {
                        draw.line(0, 0, this.contentWidth(), 0).move(0, top).stroke({ color: '#DFE5ED', width: 1 });
                    }

                    // Intervals
                    if (Object.prototype.hasOwnProperty.call(this.intervals, user.id)) {
                        this.intervals[user.id].forEach(event => {
                            const leftOffset =
                                moment
                                    .tz(event.start_at, this.companyData.timezone)
                                    .tz(this.timezone)
                                    .diff(moment.tz(this.start, this.timezone).startOf('day'), 'hours', true) % 24;
                            const widthIntrevals =
                                ((Math.max(event.duration, 60) + 120) * this.columnWidth()) / 60 / 60;
                            const rectInterval = draw
                                .rect(widthIntrevals, rowHeight / 2)
                                .move(Math.floor(leftOffset * this.columnWidth()), top + rowHeight / 4)
                                .radius(2)
                                .stroke({ color: 'transparent', width: 0 })
                                .attr({
                                    cursor: 'pointer',
                                    hoverCursor: 'pointer',
                                    fill: event.is_manual == '1' ? '#c4b52d' : '#2DC48D',
                                });

                            rectInterval.on('mouseover', e => {
                                const rectBBox = rectInterval.bbox();
                                const popupX = Math.max(rectBBox.x, canvasPadding / 2) - this.offsetX;
                                const popupY = rectBBox.y - 10;
                                this.hoverPopup = {
                                    show: true,
                                    x: popupX,
                                    y: popupY,
                                    event,
                                    borderX: defaultCornerOffset,
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
                                const rectBBox = rectInterval.bbox();
                                const popupX = Math.max(rectBBox.x, canvasPadding / 2) - this.offsetX;
                                const popupY = rectBBox.y - 50;
                                this.clickPopup = {
                                    show: true,
                                    x: popupX,
                                    y: popupY,
                                    event,
                                    borderX: defaultCornerOffset + e.target.attributes.x.value - maxLeftOffset,
                                };
                                e.stopPropagation();
                            });
                            squaresGroup.add(rectInterval);
                        });
                    }
                });
            }, 100),
            onResize: throttle(function () {
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const height = this.users.length * rowHeight;
                this.draw.size(width, height);
                this.draw.viewbox(0, 20, width, height);
                this.drawGrid();
            }, 100),
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
            start() {
                this.resetScroll();
            },
            users() {
                this.onResize();
            },
            intervals() {
                this.drawGrid();
            },
            timezone() {
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
        .scroll-area-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            display: block;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .scroll-area {
            position: absolute;
            top: 0;
            left: 0;
            right: -6px;
            bottom: -6px;
            display: block;
            overflow: auto;
            scrollbar-width: thin;

            &::-webkit-scrollbar {
                display: none;
            }
        }
        .scrollbar-top {
            position: absolute;
            left: 0;
            top: -25px;
            width: 100%;
            height: 10px;
            overflow-x: auto;
        }
        .scrollbar-top {
            scrollbar-color: #2e2ef9 transparent;
            scrollbar-width: thin;

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
    }
</style>
