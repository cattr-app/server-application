<template>
    <div class="canvas-wrapper">
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
                bottom: `${height() - clickPopup.y + 50}px`,
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
        <div ref="canvas" class="canvas" @pointerdown="onDown">
            <div ref="scrollbarTop" class="scrollbar-top" @scroll="onScroll">
                <div :style="{ width: `${totalWidth}px` }" />
            </div>
        </div>
    </div>
</template>

<script>
    import Screenshot from '@/components/Screenshot';
    import ScreenshotModal from '@/components/ScreenshotModal';
    import IntervalService from '@/services/resource/time-interval.service';
    import { formatDurationString } from '@/utils/time';
    import moment from 'moment-timezone';
    import { mapGetters } from 'vuex';
    import { SVG } from '@svgdotjs/svg.js';

    let intervalService = new IntervalService();

    const titleHeight = 20;
    const subtitleHeight = 20;
    const rowHeight = 65;
    const columns = 24;
    const minColumnWidth = 42;
    const popupWidth = 270;
    const canvasPadding = 20;
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
                totalWidth: 0,
                scrollPos: 0,
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

            async maxScrollX() {
                return (await this.contentWidth()) - this.canvasWidth();
            },
            async scrollCanvas(movementX, setScroll = true) {
                const canvas = this.$refs.canvas;
                const clientWidth = canvas.clientWidth;
                const entireWidth = await this.contentWidth();
                const height = this.height();
                const newScrollPos = this.scrollPos - movementX;
                if (newScrollPos <= 0) {
                    this.scrollPos = 0;
                } else if (newScrollPos >= entireWidth - clientWidth) {
                    this.scrollPos = entireWidth - clientWidth;
                } else {
                    this.scrollPos = newScrollPos;
                }
                setScroll ? await this.setScroll() : null;
                this.draw.viewbox(this.scrollPos, 20, clientWidth, height);
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
            async drawGrid() {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const height = this.height();
                const columnWidth = this.columnWidth();
                const draw = this.draw;
                draw.addTo(canvasContainer).size(width, height + titleHeight + subtitleHeight);
                if (height <= 0) {
                    return;
                }
                this.draw.viewbox(0, 20, width, height);
                // Background
                const rectBackground = draw
                    .rect(await this.contentWidth(), height - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .fill('#FAFAFA')
                    .stroke({ color: '#DFE5ED', width: 1 })
                    .on('mousedown', () => this.$emit('outsideClick'));
                draw.add(rectBackground);
                for (let column = 0; column < columns; ++column) {
                    const date = moment().startOf('day').add(column, 'hours');
                    let left = this.columnWidth() * column;
                    // Column headers - hours
                    draw.text(date.format('h'))
                        .move(left + columnWidth / 2, 0)
                        .size(columnWidth, titleHeight)
                        .attr({
                            'text-anchor': 'middle',
                            'font-family': 'Nunito, sans-serif',
                            'font-size': 15,
                            fill: '#151941',
                        });
                    // Column headers - am/pm
                    draw.text(date.format('A'))
                        .move(left + columnWidth / 2, titleHeight - 5)
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
                    .rect(await this.contentWidth(), height - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .attr({
                        absolutePositioned: true,
                    });
                const squaresGroup = draw.group().clipWith(clipPath);
                for (const user of this.users) {
                    const row = this.users.indexOf(user);
                    const top = row * rowHeight + titleHeight + subtitleHeight;

                    // Horizontal grid lines
                    if (row > 0) {
                        draw.line(0, 0, await this.contentWidth(), 0)
                            .move(0, top)
                            .stroke({ color: '#DFE5ED', width: 1 });
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
                                const popupY = rectInterval.bbox().y - rectInterval.bbox().height;
                                const canvasRight = this.$refs.canvas.getBoundingClientRect().right;
                                const rectMiddleX = rectInterval.rbox().cx - defaultCornerOffset / 2;
                                const minLeft = this.$refs.canvas.getBoundingClientRect().left;
                                const left =
                                    rectMiddleX > canvasRight
                                        ? canvasRight - defaultCornerOffset / 2
                                        : rectMiddleX < minLeft
                                          ? minLeft - defaultCornerOffset / 2
                                          : rectMiddleX;
                                const maxRight = canvasRight - popupWidth + 2 * canvasPadding;
                                const popupX = left > maxRight ? maxRight : left < minLeft ? minLeft : left;
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
                                const popupY = rectInterval.bbox().y - rectInterval.bbox().height;
                                const canvasRight = this.$refs.canvas.getBoundingClientRect().right;
                                const rectMiddleX = rectInterval.rbox().cx - defaultCornerOffset / 2;
                                const minLeft = this.$refs.canvas.getBoundingClientRect().left;
                                const left =
                                    rectMiddleX > canvasRight
                                        ? canvasRight - defaultCornerOffset / 2
                                        : rectMiddleX < minLeft
                                          ? minLeft - defaultCornerOffset / 2
                                          : rectMiddleX;
                                const maxRight = canvasRight - popupWidth + 2 * canvasPadding;
                                const popupX = left > maxRight ? maxRight : left < minLeft ? minLeft : left;
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
                            squaresGroup.add(rectInterval);
                        });
                    }
                }
            },
            onResize: function () {
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const height = this.height();
                this.draw.size(width, height);
                this.setScroll(0);
                this.drawGrid();
            },
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
                this.setScroll(0);
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
        .scrollbar-top {
            position: absolute;
            left: 0;
            top: -1.5rem;
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
    }
    @media (max-width: 720px) {
        .canvas {
            .scrollbar-top {
                top: -1rem;
            }
        }
    }
</style>
