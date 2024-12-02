<template>
    <div ref="canvas" class="canvas"></div>
</template>

<script>
    import moment from 'moment';
    import { formatDurationString } from '@/utils/time';
    import { SVG } from '@svgdotjs/svg.js';
    import debounce from 'lodash/debounce';

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
            this.drawGrid();
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
        },

        methods: {
            formatDuration: formatDurationString,
            drawGrid: debounce(
                function () {
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

                    draw.rect(width - 2, rows * rowHeight - 1)
                        .move(1, headerHeight)
                        .radius(20)
                        .fill('#FAFAFA')
                        .stroke({ color: '#dfe5ed', width: 1 });
                    for (let column = 0; column < columns; column++) {
                        const date = firstDay.clone().locale(this.$i18n.locale).add(column, 'days');
                        const dateFormat = window.matchMedia('(max-width: 880px)').matches ? 'ddd' : 'dddd';
                        draw.text(date.format(dateFormat).toUpperCase())
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

                    this.drawCells(draw, firstDay, columnWidth, rows, width, lastDay);
                    this.drawGridLines(draw, rows, width, columnWidth);
                },
                30,
                { maxWait: 50 },
            ),
            drawGridLines(draw, rows, width, columnWidth) {
                for (let row = 1; row < rows; row++) {
                    draw.line(1, row * rowHeight + headerHeight, width - 1, row * rowHeight + headerHeight).stroke({
                        color: '#DFE5ED',
                        width: 1,
                    });
                }

                for (let column = 1; column < columns; column++) {
                    draw.line(
                        column * columnWidth,
                        headerHeight,
                        column * columnWidth,
                        headerHeight + rowHeight * rows - 1,
                    ).stroke({
                        color: '#DFE5ED',
                        width: 1,
                    });
                }
            },
            drawCells(draw, firstDay, columnWidth, rows, width, lastDay) {
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

                        if (isInSelection) {
                            const square = draw.rect(columnWidth - 2, rowHeight - 2).attr({
                                fill: '#F4F4FF',
                                x: cellLeft + 1,
                                y: cellTop + 1,
                            });

                            squaresGroup.add(square);

                            const line = draw
                                .line(
                                    column * columnWidth,
                                    (row + 1) * rowHeight + headerHeight - 2,
                                    (column + 1) * columnWidth,
                                    (row + 1) * rowHeight + headerHeight - 2,
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
                        if (timePerDay[dateKey]) {
                            draw.text(this.formatDuration(timePerDay[dateKey]))
                                .move(cellLeft, cellTop + rowHeight - 30)
                                .attr({
                                    'text-anchor': 'inherit',
                                    'font-family': 'Nunito, sans-serif',
                                    'font-weight': isInSelection ? 600 : 400,
                                    'my-text-type': 'time',
                                    fill: '#59566E',
                                });
                        }
                    }
                }
                let clip = draw.clip();
                clip.add(
                    draw
                        .rect(width - 4, (lastDay.diff(firstDay, 'weeks') + 1) * rowHeight - 1.5)
                        .move(2, headerHeight)
                        .radius(20),
                );
                squaresGroup.clipWith(clip);
            },
            onResize: function () {
                this.drawGrid();
            },
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
        user-select: none;
        width: 100%;
        text[my-text-type='time'] {
            font-size: 0.9rem;
            transform: translateX(13px);
        }
        @media (max-width: 980px) {
            text[my-text-type='time'] {
                font-size: 0.7rem;
                transform: translateX(7px);
            }
        }
        @media (max-width: 430px) {
            text[my-text-type='time'] {
                font-size: 0.6rem;
                transform: translateX(3px);
            }
        }
    }
</style>
