<template>
    <div ref="canvasWrapper" class="canvas">
        <canvas ref="canvas" />
    </div>
</template>

<script>
    import { fabric } from 'fabric';
    import throttle from 'lodash/throttle';
    import moment from 'moment';
    import { formatDurationString } from '@/utils/time';

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

    const headerHeight = 20;
    const columns = 7;
    const rowHeight = 120;

    export default {
        name: 'TimelineCalendarGraph',
        props: {
            start: {
                type: String,
                required: true,
            },
            end: {
                type: String,
                required: true,
            },
            timePerDay: {
                type: Object,
                required: true,
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
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
        },
        methods: {
            formatDuration: formatDurationString,
            draw: throttle(function () {
                this.canvas.clear();

                const width = this.canvas.getWidth();
                const columnWidth = width / 7;

                const startOfMonth = moment(this.start, 'YYYY-MM-DD').startOf('month');
                const endOfMonth = moment(this.start, 'YYYY-MM-DD').endOf('month');

                const firstDay = startOfMonth.clone().startOf('isoWeek');
                const lastDay = endOfMonth.clone().endOf('isoWeek');

                const rows = lastDay.diff(firstDay, 'weeks') + 1;

                // Background
                this.canvas.add(
                    new fabric.Rect({
                        left: 0,
                        top: headerHeight,
                        width: width - 1,
                        height: rows * rowHeight - 1,
                        rx: 20,
                        ry: 20,
                        fill: '#FAFAFA',
                        stroke: '#DFE5ED',
                        strokeWidth: 1,
                        ...fabricObjectOptions,
                    }),
                );

                // Column headers
                for (let column = 0; column < columns; column++) {
                    const date = firstDay.clone().locale(this.$i18n.locale).add(column, 'days');
                    this.canvas.add(
                        new fabric.Textbox(date.format('dddd').toUpperCase(), {
                            left: column * columnWidth,
                            top: 0,
                            width: columnWidth,
                            height: headerHeight,
                            textAlign: 'center',
                            fontFamily: 'Nunito, sans-serif',
                            fontSize: 10,
                            fontWeight: 600,
                            fill: '#2E2EF9',
                            ...fabricObjectOptions,
                        }),
                    );
                }

                const { timePerDay } = this;
                for (let row = 0; row < rows; row++) {
                    for (let column = 0; column < columns; column++) {
                        const cellLeft = column * columnWidth;
                        const cellTop = headerHeight + row * rowHeight;
                        const date = firstDay.clone().add(row * columns + column, 'days');

                        const isInSelection = date.diff(this.start) >= 0 && date.diff(this.end) <= 0;
                        const isInSelectedMonth = date.diff(startOfMonth) >= 0 && date.diff(endOfMonth) <= 0;

                        // Selected cell background
                        if (isInSelection) {
                            this.canvas.add(
                                new fabric.Rect({
                                    left: cellLeft + 1,
                                    top: cellTop + 1,
                                    width: columnWidth - 2,
                                    height: rowHeight - 2,
                                    fill: '#F4F4FF',
                                    strokeWidth: 0,
                                    clipPath: new fabric.Rect({
                                        left: 0,
                                        top: headerHeight,
                                        width: width - 1,
                                        height: rows * rowHeight - 1,
                                        rx: 20,
                                        ry: 20,
                                        absolutePositioned: true,
                                    }),
                                    ...fabricObjectOptions,
                                }),
                            );
                        }

                        // Date label
                        this.canvas.add(
                            new fabric.Textbox(date.format('D'), {
                                left: cellLeft,
                                top: cellTop + 10,
                                width: columnWidth - 13,
                                height: rowHeight,
                                textAlign: 'right',
                                fontFamily: 'Nunito, sans-serif',
                                fontSize: 15,
                                fontWeight: isInSelection ? 600 : 400,
                                fill: isInSelection ? '#2E2EF9' : isInSelectedMonth ? '#59566E' : '#B1B1BE',
                                ...fabricObjectOptions,
                            }),
                        );

                        // Worked time label
                        const dateKey = date.format('YYYY-MM-DD');
                        if (timePerDay[dateKey]) {
                            this.canvas.add(
                                new fabric.Textbox(this.formatDuration(timePerDay[dateKey]), {
                                    left: cellLeft + 13,
                                    top: cellTop + rowHeight - 30,
                                    width: columnWidth,
                                    height: rowHeight,
                                    textAlign: 'left',
                                    fontFamily: 'Nunito, sans-serif',
                                    fontSize: 15,
                                    fontWeight: isInSelection ? 600 : 400,
                                    fill: '#59566E',
                                    ...fabricObjectOptions,
                                }),
                            );
                        }

                        // Selected cell bottom border
                        if (isInSelection) {
                            this.canvas.add(
                                new fabric.Line([0, 0, columnWidth, 0], {
                                    left: columnWidth * column,
                                    top: cellTop + rowHeight - 3,
                                    stroke: '#2E2EF9',
                                    strokeWidth: 3,
                                    rx: 1.5,
                                    ry: 1.5,
                                    clipPath: new fabric.Rect({
                                        left: 0,
                                        top: headerHeight,
                                        width: width - 1,
                                        height: rows * rowHeight - 1,
                                        rx: 20,
                                        ry: 20,
                                        absolutePositioned: true,
                                    }),
                                    ...fabricObjectOptions,
                                }),
                            );
                        }
                    }
                }

                // Horizontal grid lines
                for (let row = 1; row < rows; row++) {
                    this.canvas.add(
                        new fabric.Line([0, 0, width, 0], {
                            left: 0,
                            top: rowHeight * row + headerHeight,
                            stroke: '#DFE5ED',
                            strokeWidth: 1,
                            ...fabricObjectOptions,
                        }),
                    );
                }

                // Vertical grid lines
                for (let column = 1; column < columns; column++) {
                    this.canvas.add(
                        new fabric.Line([0, 0, 0, rowHeight * rows], {
                            left: columnWidth * column,
                            top: 20,
                            stroke: '#DFE5ED',
                            strokeWidth: 1,
                            ...fabricObjectOptions,
                        }),
                    );
                }

                this.canvas.requestRenderAll();
            }),
            onResize: throttle(function () {
                if (!this.$refs.canvasWrapper) {
                    return;
                }
                const { width } = this.$refs.canvasWrapper.getBoundingClientRect();
                this.canvas.setWidth(width);

                const startOfMonth = moment(this.start, 'YYYY-MM-DD').startOf('month');
                const endOfMonth = moment(this.start, 'YYYY-MM-DD').endOf('month');
                const firstDay = startOfMonth.startOf('isoWeek');
                const lastDay = endOfMonth.endOf('isoWeek');
                const rows = lastDay.diff(firstDay, 'weeks') + 1;
                const height = headerHeight + rowHeight * rows;
                this.canvas.setHeight(height);

                this.draw();
            }, 100),
        },
        watch: {
            start() {
                this.onResize();
            },
            end() {
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
</style>
