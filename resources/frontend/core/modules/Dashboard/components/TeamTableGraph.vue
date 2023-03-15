<template>
    <div ref="canvasWrapper" class="canvas">
        <canvas ref="canvas" />

        <div ref="scrollbarTop" class="scrollbar-top" @scroll="onScroll">
            <div :style="{ width: `${contentWidth}px` }" />
        </div>

        <div class="scroll-area-wrapper">
            <div
                ref="scrollArea"
                class="scroll-area"
                @scroll="onScroll"
                @pointerdown="onDown"
                @pointermove="onMove"
                @pointerup="onUp"
            >
                <div class="scroll-area-inner" :style="{ width: `${contentWidth}px` }" />
            </div>
        </div>
    </div>
</template>

<script>
    import { fabric } from 'fabric';
    import throttle from 'lodash/throttle';
    import moment from 'moment';
    import { formatDurationString } from '@/utils/time';
    import { mapGetters } from 'vuex';

    const defaultColorConfig = [
        {
            start: 0,
            end: 0.75,
            color: '#ffb6c2',
        },
        {
            start: 0.76,
            end: 1,
            color: '#93ecda',
        },
        {
            start: 1,
            end: 0,
            color: '#3cd7b6',
            isOverTime: true,
        },
    ];

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
    const minColumnWidth = 85;

    export default {
        name: 'TeamTableGraph',
        props: {
            start: {
                type: String,
                required: true,
            },
            end: {
                type: String,
                required: true,
            },
            users: {
                type: Array,
                required: true,
            },
            timePerDay: {
                type: Object,
                required: true,
            },
        },
        data() {
            return {
                canvas: null,
                isDragging: false,
                lastPosX: 0,
            };
        },
        computed: {
            ...mapGetters('user', ['companyData']),
            workingHours() {
                return 'work_time' in this.companyData && this.companyData.work_time ? this.companyData.work_time : 7;
            },
            colorRules() {
                return this.companyData.color ? this.companyData.color : defaultColorConfig;
            },
            canvasWidth() {
                if (!this.canvas) {
                    return 500;
                }

                return this.canvas.getWidth();
            },
            columns() {
                const start = moment(this.start, 'YYYY-MM-DD');
                const end = moment(this.end, 'YYYY-MM-DD');

                return end.diff(start, 'days') + 1;
            },
            columnWidth() {
                return Math.max(minColumnWidth, this.canvasWidth / this.columns);
            },
            contentWidth() {
                return this.columns * this.columnWidth;
            },
            maxScrollX() {
                return this.contentWidth - this.canvasWidth;
            },
        },
        mounted() {
            this.canvas = new fabric.Canvas(this.$refs.canvas, {
                backgroundColor: '#ffffff',
                renderOnAddRemove: false,
                selection: false,
                skipOffscreen: true,
            });

            this.onResize();
            window.addEventListener('resize', this.onResize);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
        },
        methods: {
            getColor(progress) {
                let color = '#3cd7b6';

                this.colorRules.forEach(el => {
                    if ('isOverTime' in el && progress > el.start) {
                        color = el.color;
                    } else if (progress >= el.start && progress <= el.end) {
                        color = el.color;
                    }
                });

                return color;
            },
            onDown(e) {
                this.canvas.selection = false;
                this.isDragging = true;
                this.lastPosX = e.clientX;
            },
            onMove(e) {
                if (this.isDragging) {
                    const deltaX = e.clientX - this.lastPosX;
                    const x = Math.min(0, Math.max(this.canvas.viewportTransform[4] + deltaX, -this.maxScrollX));

                    this.setScroll(x);
                    this.lastPosX = e.clientX;
                }
            },
            onUp(e) {
                this.isDragging = false;
            },
            onScroll(e) {
                this.setScroll(-e.target.scrollLeft);
            },
            setScroll(x) {
                if (x === this.canvas.viewportTransform[4]) {
                    return;
                }

                this.$refs.scrollbarTop.scrollLeft = -x;
                this.$refs.scrollArea.scrollLeft = -x;

                const transform = [...this.canvas.viewportTransform];
                transform[4] = x;

                this.canvas.setViewportTransform(transform);
                this.canvas.requestRenderAll();
            },
            resetScroll() {
                this.setScroll(0);
            },
            formatDuration: formatDurationString,
            draw: throttle(function () {
                this.canvas.clear();

                const width = this.contentWidth;
                const height = this.users.length * rowHeight;

                const start = moment(this.start, 'YYYY-MM-DD');

                const cursor = this.contentWidth > this.canvasWidth ? 'move' : 'default';

                // Background
                this.canvas.add(
                    new fabric.Rect({
                        left: 0,
                        top: titleHeight + subtitleHeight,
                        width: width - 1,
                        height: height - 1,
                        rx: 20,
                        ry: 20,
                        fill: '#fafafa',
                        stroke: '#dfe5ed',
                        strokeWidth: 1,
                        ...fabricObjectOptions,
                        cursor,
                        hoverCursor: cursor,
                    }),
                );

                for (let column = 0; column < this.columns; ++column) {
                    const date = start.clone().locale(this.$i18n.locale).add(column, 'days');
                    const left = this.columnWidth * column;

                    // Column headers - day
                    this.canvas.add(
                        new fabric.Textbox(date.locale(this.$i18n.locale).format('D'), {
                            left,
                            top: 0,
                            width: this.columnWidth,
                            height: titleHeight,
                            textAlign: 'center',
                            fontFamily: 'Nunito, sans-serif',
                            fontSize: 15,
                            fill: '#151941',
                            ...fabricObjectOptions,
                            cursor,
                            hoverCursor: cursor,
                        }),
                    );

                    // Column headers - am/pm
                    this.canvas.add(
                        new fabric.Textbox(date.format('dddd').toUpperCase(), {
                            left,
                            top: titleHeight,
                            width: this.columnWidth,
                            height: subtitleHeight,
                            textAlign: 'center',
                            fontFamily: 'Nunito, sans-serif',
                            fontSize: 10,
                            fontWeight: '600',
                            fill: '#b1b1be',
                            ...fabricObjectOptions,
                            cursor,
                            hoverCursor: cursor,
                        }),
                    );

                    // Vertical grid lines
                    if (column > 0) {
                        this.canvas.add(
                            new fabric.Line([0, 0, 0, height], {
                                left,
                                top: titleHeight + subtitleHeight,
                                stroke: '#dfe5ed',
                                strokeWidth: 1,
                                ...fabricObjectOptions,
                                cursor,
                                hoverCursor: cursor,
                            }),
                        );
                    }
                }

                const countAllRows = this.users.length - 1;
                const countAllColumns = this.columns - 1;

                this.users.forEach((user, row) => {
                    const top = row * rowHeight + titleHeight + subtitleHeight;
                    const userTime = this.timePerDay[user.id];

                    if (userTime) {
                        Object.keys(userTime).forEach((day, i) => {
                            const column = -start.diff(day, 'days');
                            const duration = userTime[day];
                            const left = column * this.columnWidth;
                            const total = 60 * 60 * this.workingHours;
                            const progress = duration / total;
                            const height = Math.ceil(Math.min(progress, 1) * (rowHeight - 1));
                            const color = this.getColor(progress);

                            if (column === 0 && row === 0) {
                                // Cell background
                                this.canvas.add(
                                    new fabric.Rect({
                                        left: left + 1,
                                        top: Math.floor(top + (rowHeight - height)),
                                        width: this.columnWidth,
                                        height,
                                        fill: color,
                                        strokeWidth: 0,
                                        ...fabricObjectOptions,
                                        cursor,
                                        hoverCursor: cursor,
                                        clipPath: new fabric.Rect({
                                            left: 0,
                                            top: titleHeight + subtitleHeight,
                                            width: this.contentWidth,
                                            height: this.users.length * rowHeight,
                                            rx: 20,
                                            ry: 20,
                                            absolutePositioned: true,
                                        }),
                                    }),
                                );
                            } else if (column === 0 && row === countAllRows) {
                                this.canvas.add(
                                    new fabric.Rect({
                                        left: left + 1,
                                        top: Math.floor(top + (rowHeight - height)),
                                        width: this.columnWidth,
                                        height,
                                        fill: color,
                                        strokeWidth: 0,
                                        ...fabricObjectOptions,
                                        cursor,
                                        hoverCursor: cursor,
                                        clipPath: new fabric.Rect({
                                            left: 0,
                                            top: titleHeight + subtitleHeight,
                                            width: this.contentWidth,
                                            height: this.users.length * rowHeight,
                                            rx: 20,
                                            ry: 20,
                                            absolutePositioned: true,
                                        }),
                                    }),
                                );
                            } else if (countAllColumns === column && row === 0) {
                                this.canvas.add(
                                    new fabric.Rect({
                                        left: left + 1,
                                        top: Math.floor(top + (rowHeight - height)),
                                        width: this.columnWidth,
                                        height,
                                        fill: color,
                                        strokeWidth: 0,
                                        ...fabricObjectOptions,
                                        cursor,
                                        hoverCursor: cursor,
                                        clipPath: new fabric.Rect({
                                            left: 0,
                                            top: titleHeight + subtitleHeight,
                                            width: this.contentWidth,
                                            height: this.users.length * rowHeight,
                                            rx: 20,
                                            ry: 20,
                                            absolutePositioned: true,
                                        }),
                                    }),
                                );
                            } else if (countAllColumns === column && row === countAllRows) {
                                this.canvas.add(
                                    new fabric.Rect({
                                        left: left + 1,
                                        top: Math.floor(top + (rowHeight - height)),
                                        width: this.columnWidth,
                                        height,
                                        fill: color,
                                        strokeWidth: 0,
                                        ...fabricObjectOptions,
                                        cursor,
                                        hoverCursor: cursor,
                                        clipPath: new fabric.Rect({
                                            left: 0,
                                            top: titleHeight + subtitleHeight,
                                            width: this.contentWidth,
                                            height: this.users.length * rowHeight,
                                            rx: 20,
                                            ry: 20,
                                            absolutePositioned: true,
                                        }),
                                    }),
                                );
                            } else {
                                this.canvas.add(
                                    new fabric.Rect({
                                        left: left + 1,
                                        top: Math.floor(top + (rowHeight - height)),
                                        width: this.columnWidth,
                                        height,
                                        fill: color,
                                        strokeWidth: 0,
                                        ...fabricObjectOptions,
                                        cursor,
                                        hoverCursor: cursor,
                                    }),
                                );
                            }

                            // Time label
                            this.canvas.add(
                                new fabric.Textbox(this.formatDuration(duration), {
                                    left,
                                    top: top + 22,
                                    width: this.columnWidth,
                                    height: rowHeight,
                                    textAlign: 'center',
                                    fontFamily: 'Nunito, sans-serif',
                                    fontSize: 15,
                                    fontWeight: '600',
                                    fill: '#151941',
                                    ...fabricObjectOptions,
                                    cursor,
                                    hoverCursor: cursor,
                                }),
                            );
                        });
                    }

                    // Horizontal grid lines
                    if (row > 0) {
                        this.canvas.add(
                            new fabric.Line([0, 0, width, 0], {
                                left: 0,
                                top,
                                stroke: '#dfe5ed',
                                strokeWidth: 1,
                                ...fabricObjectOptions,
                                cursor,
                                hoverCursor: cursor,
                            }),
                        );
                    }
                });

                this.canvas.requestRenderAll();
            }, 100),
            onResize: throttle(function () {
                if (!this.$refs.canvasWrapper) {
                    return;
                }
                const { width } = this.$refs.canvasWrapper.getBoundingClientRect();
                const height = this.users.length * rowHeight + titleHeight + subtitleHeight;
                this.canvas.setWidth(width);
                this.canvas.setHeight(height);
                this.draw();
            }, 100),
        },
        watch: {
            start() {
                this.resetScroll();
            },
            end() {
                this.resetScroll();
            },
            users() {
                this.onResize();
            },
            timePerDay() {
                this.draw();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .canvas::v-deep canvas {
        box-sizing: content-box;
    }

    .canvas {
        position: relative;
    }

    .scrollbar-top {
        position: absolute;
        left: 0;
        top: -25px;
        width: 100%;
        overflow-x: auto;
    }

    .scrollbar-bottom {
        position: absolute;
        left: 0;
        bottom: -5px;
        width: 100%;
        overflow-x: auto;
    }

    .scrollbar-bottom,
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

    .scroll-area-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        display: block;
        width: 100%;
        height: 100%;
        overflow: hidden;
        cursor: move;
    }

    .scroll-area {
        position: absolute;
        top: 0;
        left: 0;
        right: -6px;
        bottom: -6px;
        display: block;
        overflow: scroll;
        scrollbar-width: thin;

        &::-webkit-scrollbar {
            display: none;
        }
    }

    .scroll-area-inner {
        display: block;
        width: 100%;
        height: 100%;
    }
</style>
