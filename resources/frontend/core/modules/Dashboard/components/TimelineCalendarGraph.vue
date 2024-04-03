<template>
    <div ref="canvas" class="canvas"></div>
</template>

<script>
    import throttle from 'lodash/throttle';
    import moment from 'moment';
    import { formatDurationString } from '@/utils/time';
    import { SVG } from '@svgdotjs/svg.js';
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
            this.draw = SVG();
            window.addEventListener('resize', this.onResize);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
        },

        methods: {
            formatDuration: formatDurationString,
            drawGrid: throttle(function () {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();

                const startOfMonth = moment(this.start, 'YYYY-MM-DD').startOf('month');
                const endOfMonth = moment(this.start, 'YYYY-MM-DD').endOf('month');
                const firstDay = startOfMonth.clone().startOf('isoWeek');
                const lastDay = endOfMonth.clone().endOf('isoWeek');
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const columnWidth = width / 7;
                const rows = lastDay.diff(firstDay, 'weeks') + 1;
                const draw = this.draw;
                draw.addTo(canvasContainer).size(width, headerHeight + rowHeight * 6);

                draw.rect(width - 1, (lastDay.diff(firstDay, 'weeks') + 1) * rowHeight - 1)
                    .move(0, headerHeight)
                    .radius(20)
                    .fill('#FAFAFA')
                    .stroke({ color: '#dfe5ed', width: 1 });
                for (let column = 0; column < columns; column++) {
                    const date = firstDay.clone().locale(this.$i18n.locale).add(column, 'days');
                    draw.text(date.format('dddd').toUpperCase())
                        .move(column * columnWidth + columnWidth / 2, -5)
                        .width(columnWidth)
                        .height(headerHeight)
                        .attr({
                            'text-anchor': 'middle',
                            'font-family': 'Nunito, sans-serif',
                            'font-size': 10,
                            'font-weight': 600,
                            fill: '#2E2EF9',
                        });
                }

                this.drawCells(draw, firstDay, columnWidth, rows, true, width, lastDay);
                this.drawBackgroundGrid(draw, rows, width, columnWidth);
                this.drawCells(draw, firstDay, columnWidth, rows, false, width, lastDay);
            }),
            drawBackgroundGrid(draw, rows, width, columnWidth) {
                for (let row = 1; row < rows; row++) {
                    draw.line(0, row * rowHeight + headerHeight, width, row * rowHeight + headerHeight).stroke({
                        color: '#DFE5ED',
                        width: 1,
                    });
                }

                for (let column = 1; column < columns; column++) {
                    draw.line(
                        column * columnWidth,
                        headerHeight,
                        column * columnWidth,
                        headerHeight + rowHeight * rows,
                    ).stroke({
                        color: '#DFE5ED',
                        width: 1,
                    });
                }
            },
            drawCells(draw, firstDay, columnWidth, rows, cellBackground, width, lastDay) {
                const squaresGroup = draw.group();

                for (let row = 0; row < rows; row++) {
                    for (let column = 0; column < columns; column++) {
                        const date = firstDay
                            .clone()
                            .locale(this.$i18n.locale)
                            .add(row * columns + column, 'days');
                        const cellLeft = column * columnWidth;
                        const cellTop = headerHeight + row * rowHeight;
                        const isInSelection = date.diff(this.start) >= 0 && date.diff(this.end) <= 0;
                        const { timePerDay } = this;
                        if (isInSelection && cellBackground) {
                            const square = draw.rect(columnWidth - 2, rowHeight - 2).attr({
                                fill: '#F4F4FF',
                                x: cellLeft + 1,
                                y: cellTop + 1,
                            });

                            squaresGroup.add(square);
                        } else {
                            if (isInSelection) {
                                const line = draw
                                    .line(
                                        column * columnWidth,
                                        (row + 1) * rowHeight + headerHeight,
                                        (column + 1) * columnWidth,
                                        (row + 1) * rowHeight + headerHeight,
                                    )
                                    .stroke({
                                        color: '#2E2EF9',
                                        width: 3,
                                    });
                                squaresGroup.add(line);
                            }
                            draw.text(date.format('D'))
                                .move((column + 1) * columnWidth - 10, row * rowHeight + headerHeight + 5)
                                .attr({
                                    'text-anchor': 'end',
                                    'font-family': 'Nunito, sans-serif',
                                    'font-size': 12,
                                    'font-weight': isInSelection ? 600 : 400,
                                    fill: isInSelection ? '#2E2EF9' : '#868495',
                                });

                            const dateKey = date.format('YYYY-MM-DD');
                            if (timePerDay[dateKey] && isInSelection) {
                                draw.text(this.formatDuration(timePerDay[dateKey]))
                                    .move(cellLeft + 13, cellTop + rowHeight - 30)
                                    .attr({
                                        'text-anchor': 'inherit',
                                        'font-family': 'Nunito, sans-serif',
                                        'font-size': 15,
                                        'font-weight': 600,
                                        fill: '#59566E',
                                    });
                            }
                        }
                    }
                }
                let clip = draw.clip();
                clip.add(
                    draw
                        .rect(width - 1, (lastDay.diff(firstDay, 'weeks') + 1) * rowHeight + 1)
                        .move(0, headerHeight)
                        .radius(20),
                );
                squaresGroup.clipWith(clip);
            },
            onResize: throttle(function () {
                this.drawGrid();
            }, 0),
        },

        watch: {
            start() {
                this.onResize();
            },
            end() {
                this.onResize();
            },
            timePerDay() {
                this.drawGrid();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .canvas ::v-deep svg {
        width: 100%;
    }
</style>
