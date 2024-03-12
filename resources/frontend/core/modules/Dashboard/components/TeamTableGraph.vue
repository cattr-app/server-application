<template>
    <div class="canvas">
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
                <div class="scroll-area-inner" :style="{ width: `${contentWidth}px` }"><div ref="canvas"></div></div>
            </div>
        </div>
    </div>
</template>

<script>
    import throttle from 'lodash/throttle';
    import moment from 'moment';
    import { formatDurationString } from '@/utils/time';
    import { mapGetters } from 'vuex';
    import { SVG } from '@svgdotjs/svg.js';

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
    const columns = 7;
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
            columns() {
                const start = moment(this.start, 'YYYY-MM-DD');
                const end = moment(this.end, 'YYYY-MM-DD');

                return end.diff(start, 'days') + 1;
            },
            columnWidth() {
                return Math.max(minColumnWidth, this.canvasWidth() / this.columns);
            },
            contentWidth() {
                return this.columns * this.columnWidth;
            },
            maxScrollX() {
                return this.contentWidth - this.canvasWidth();
            },
        },
        mounted() {
            this.offsetX = 0;
            this.draw = SVG();
            this.onResize();
            window.addEventListener('resize', this.onResize);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
        },
        methods: {
            canvasWidth() {
                if (!this.$refs.scrollArea) {
                    return 500;
                }

                return this.$refs.scrollArea.clientWidth;
            },
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
                this.draw.selection = false;
                this.isDragging = true;
                this.lastPosX = e.clientX;
            },
            onMove(e) {
                if (this.isDragging) {
                    const deltaX = e.clientX - this.lastPosX;
                    let newScrollX = this.draw.transform().translateX + deltaX;
                    newScrollX = Math.min(0, Math.max(newScrollX, -this.maxScrollX));

                    this.setScroll(newScrollX);
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
                this.offsetX = x;
                this.draw.transform({ translateX: x });
            },
            resetScroll() {
                this.setScroll(0);
            },
            formatDuration: formatDurationString,
            drawGrid: throttle(function () {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();
                const draw = this.draw;
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const height = this.users.length * rowHeight;
                const columnWidth = width / this.columns;
                const start = moment(this.start, 'YYYY-MM-DD');

                const cursor = this.contentWidth > this.canvasWidth() ? 'move' : 'default';
                draw.addTo(canvasContainer).size(width, height + titleHeight + subtitleHeight);
                // Background
                draw.rect(width - 1, height - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .fill('#fafafa')
                    .stroke({ color: '#dfe5ed', width: 1 })
                    .attr({
                        cursor: cursor,
                        hoverCursor: cursor,
                    });

                for (let column = 0; column < this.columns; ++column) {
                    const date = start.clone().locale(this.$i18n.locale).add(column, 'days');
                    let left = this.columnWidth * column;
                    let halfColumnWidth = this.columnWidth / 2;
                    if (this.columns === 7) {
                        left = columnWidth * column;
                        halfColumnWidth = columnWidth / 2;
                    }
                    // Column headers - day
                    draw.text(date.locale(this.$i18n.locale).format('D'))
                        .move(left + halfColumnWidth, 0)
                        .size(columnWidth, titleHeight)
                        .font({
                            family: 'Nunito, sans-serif',
                            size: 15,
                            fill: '#151941',
                        })
                        .attr({
                            'text-anchor': 'middle',
                            cursor: cursor,
                            hoverCursor: cursor,
                        });

                    // Column headers - am/pm
                    draw.text(date.format('dddd').toUpperCase())
                        .move(left + halfColumnWidth, titleHeight - 5)
                        .size(columnWidth, subtitleHeight)
                        .font({
                            family: 'Nunito, sans-serif',
                            size: 10,
                            weight: '600',
                            fill: '#b1b1be',
                        })
                        .attr({
                            'text-anchor': 'middle',
                            cursor: cursor,
                            hoverCursor: cursor,
                        });

                    // Vertical grid lines
                    if (column > 0) {
                        draw.line(0, titleHeight + subtitleHeight, 0, height + titleHeight + subtitleHeight)
                            .move(left, titleHeight + subtitleHeight)
                            .stroke({ color: '#DFE5ED', width: 1 })
                            .attr({
                                cursor: cursor,
                                hoverCursor: cursor,
                            });
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
                            const left = (column * width) / this.columns;
                            const total = 60 * 60 * this.workingHours;
                            const progress = duration / total;
                            const height = Math.ceil(Math.min(progress, 1) * (rowHeight - 1));
                            const color = this.getColor(progress);
                            let squaresGroup, clipPath;

                            switch (true) {
                                case column === 0 && row === 0: {
                                    squaresGroup = draw.group();
                                    const rect1 = draw
                                        .rect(columnWidth, height)
                                        .move(left + 1, Math.floor(top + (rowHeight - height)))
                                        .fill(color)
                                        .stroke({ width: 0 })
                                        .attr({
                                            cursor: cursor,
                                            hoverCursor: cursor,
                                        });
                                    squaresGroup.add(rect1);
                                    clipPath = draw
                                        .rect(columnWidth, this.users.length * rowHeight)
                                        .move(0, titleHeight + subtitleHeight)
                                        .radius(20)
                                        .attr({
                                            absolutePositioned: true,
                                        });
                                    squaresGroup.clipWith(clipPath);
                                    break;
                                }
                                case column === 0 && row === countAllRows: {
                                    squaresGroup = draw.group();
                                    const rect2 = draw
                                        .rect(columnWidth, height)
                                        .move(left + 1, Math.floor(top + (rowHeight - height)))
                                        .fill(color)
                                        .stroke({ width: 0 });
                                    squaresGroup.add(rect2);
                                    clipPath = draw
                                        .rect(width, this.users.length * rowHeight)
                                        .move(0, titleHeight + subtitleHeight)
                                        .radius(20);
                                    squaresGroup.clipWith(clipPath);
                                    break;
                                }
                                case countAllColumns === column && row === 0: {
                                    squaresGroup = draw.group();
                                    const rect3 = draw
                                        .rect(columnWidth, height)
                                        .move(left + 1, Math.floor(top + (rowHeight - height)))
                                        .fill(color)
                                        .stroke({ width: 0 })
                                        .attr({
                                            cursor: cursor,
                                            hoverCursor: cursor,
                                        });
                                    squaresGroup.add(rect3);
                                    clipPath = draw
                                        .rect(width, this.users.length * rowHeight)
                                        .move(0, titleHeight + subtitleHeight)
                                        .radius(20)
                                        .attr({
                                            absolutePositioned: true,
                                        });
                                    squaresGroup.clipWith(clipPath);
                                    break;
                                }
                                case countAllColumns === column && row === countAllRows: {
                                    squaresGroup = draw.group();
                                    const rect4 = draw
                                        .rect(columnWidth, height)
                                        .move(left + 1, Math.floor(top + (rowHeight - height)))
                                        .fill(color)
                                        .stroke({ width: 0 })
                                        .attr({
                                            cursor: cursor,
                                            hoverCursor: cursor,
                                        });
                                    squaresGroup.add(rect4);
                                    clipPath = draw
                                        .rect(width, this.users.length * rowHeight)
                                        .move(this.offsetX, titleHeight + subtitleHeight)
                                        .radius(20)
                                        .attr({
                                            absolutePositioned: true,
                                        });
                                    squaresGroup.clipWith(clipPath);
                                    break;
                                }
                                default:
                                    draw.rect(columnWidth, height)
                                        .move(left, Math.floor(top + (rowHeight - height)))
                                        .fill(color)
                                        .stroke({ width: 0 })
                                        .attr({
                                            cursor: cursor,
                                            hoverCursor: cursor,
                                        });
                                    break;
                            }

                            // Time label
                            draw.text(this.formatDuration(duration))
                                .move(columnWidth / 2 + left, top + 22)
                                .size(this.columnWidth, rowHeight)
                                .font({
                                    family: 'Nunito, sans-serif',
                                    size: 15,
                                    weight: '600',
                                    fill: '#151941',
                                })
                                .attr({
                                    'text-anchor': 'middle',
                                    cursor: cursor,
                                    hoverCursor: cursor,
                                });
                        });
                    }

                    // Horizontal grid lines
                    if (row > 0) {
                        draw.line(0, 0, width, 0).move(0, top).stroke({ color: '#dfe5ed', width: 1 }).attr({
                            cursor: cursor,
                            hoverCursor: cursor,
                        });
                    }
                });
            }, 100),
            onResize: throttle(function () {
                this.drawGrid();
                this.drawGrid();
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
                this.drawGrid();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .canvas::v-deep canvas {
        box-sizing: content-box;
    }

    .canvas {
        height: 100%;
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
