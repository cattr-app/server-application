<template>
    <div ref="canvas" class="canvas">
        <div ref="scrollbarTop" class="scrollbar-top" @scroll="onScroll">
            <div :style="{ width: `${contentWidth()}px` }" />
        </div>
        <div class="scroll-area-wrapper">
            <div ref="scrollArea" class="scroll-area" @pointerdown="onDown" @scroll="onScroll"></div>
        </div>
    </div>
</template>

<script>
    import throttle from 'lodash/throttle';
    import moment from 'moment';
    import { formatDurationString } from '@/utils/time';
    import { mapGetters } from 'vuex';
    import { SVG } from '@svgdotjs/svg.js';
    import { debounce } from 'lodash';

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
                viewBox: '',
                lastPosX: 0,
                offsetX: 0,
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

            maxScrollX() {
                return this.contentWidth() - this.canvasWidth();
            },
        },
        mounted() {
            this.draw = SVG();

            const canvasContainer = this.$refs.scrollArea;
            const width = canvasContainer.clientWidth;
            const height = this.users.length * rowHeight;
            this.draw.viewbox(0, 0, width, height);

            this.onResize();
            window.addEventListener('resize', this.onResize);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
            document.removeEventListener('pointermove', this.onMove);
            document.removeEventListener('pointerup', this.onUp);
            document.removeEventListener('pointercancel', this.onUp);
        },
        methods: {
            canvasWidth() {
                if (!this.$refs.canvas) {
                    return 500;
                }
                return this.$refs.canvas.clientWidth;
            },
            columnWidth() {
                return Math.max(minColumnWidth, this.canvasWidth() / this.columns);
            },
            contentWidth() {
                return this.columns * this.columnWidth();
            },
            isDateWithinRange(dateString, startDate, endDate) {
                const date = new Date(dateString);
                return date >= new Date(startDate) && date <= new Date(endDate);
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
                if (e.buttons & 1) {
                    this.draw.selection = false;
                    this.lastPosX = e.clientX;
                    document.addEventListener('pointermove', this.onMove);
                    document.addEventListener('pointerup', this.onUp);
                    document.addEventListener('pointercancel', this.onUp);
                }
            },
            onMove(e) {
                if (e.buttons & 1) {
                    const deltaX = e.clientX - this.lastPosX;
                    this.offsetX -= deltaX;
                    this.offsetX = Math.min(this.maxScrollX, Math.max(this.offsetX, 0));
                    this.lastPosX = e.clientX;
                    this.setScroll(this.offsetX);
                }
            },
            onUp(e) {
                if (e.buttons & 1) {
                    document.removeEventListener('pointermove', this.onMove);
                    document.removeEventListener('pointerup', this.onUp);
                    document.removeEventListener('pointercancel', this.onUp);
                }
            },
            onScroll(e) {
                this.setScroll(e.target.scrollLeft);
            },
            setScroll(x) {
                const canvasContainer = this.$refs.scrollArea;
                const width = canvasContainer.clientWidth;
                const height = canvasContainer.clientHeight;
                this.draw.viewbox(x, 0, width, height);
            },
            resetScroll() {
                this.setScroll(0);
            },
            formatDuration: formatDurationString,
            drawGrid: debounce(function () {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();
                const draw = this.draw;
                const canvasContainer = this.$refs.scrollArea;
                const width = canvasContainer.clientWidth;
                const columnWidth = width / this.columns;
                const height = this.users.length * rowHeight;
                const start = moment(this.start, 'YYYY-MM-DD');
                draw.viewbox(0, 0, width, canvasContainer.clientHeight);
                const cursor = this.contentWidth() > this.canvasWidth() ? 'move' : 'default';
                draw.addTo(canvasContainer).size(width, height + titleHeight + subtitleHeight);
                // Background
                draw.rect(this.contentWidth() - 1, canvasContainer.clientHeight - (titleHeight + subtitleHeight))
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
                    let left = this.columnWidth() * column;
                    let halfColumnWidth = this.columnWidth() / 2;
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
                        draw.line(0, titleHeight + subtitleHeight, 0, height + titleHeight + subtitleHeight + 10)
                            .move(left, titleHeight + subtitleHeight)
                            .stroke({ color: '#DFE5ED', width: 1 })
                            .attr({
                                cursor: cursor,
                                hoverCursor: cursor,
                            });
                    }
                }

                const filteredData = {};
                for (let key in this.timePerDay) {
                    const innerObject = this.timePerDay[key];
                    const filteredInnerObject = {};
                    for (let dateKey in innerObject) {
                        if (this.isDateWithinRange(dateKey, this.start, this.end)) {
                            filteredInnerObject[dateKey] = innerObject[dateKey];
                        }
                    }
                    filteredData[key] = filteredInnerObject;
                }
                const clipPath = draw
                    .rect(this.contentWidth() - 1, canvasContainer.clientHeight - (titleHeight + subtitleHeight))
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .attr({
                        absolutePositioned: true,
                    });
                const squaresGroup = draw.group().clipWith(clipPath);
                this.users.forEach((user, row) => {
                    const top = row * rowHeight + titleHeight + subtitleHeight;
                    const userTime = filteredData[user.id];

                    if (userTime) {
                        Object.keys(userTime).forEach((day, i) => {
                            const column = -start.diff(day, 'days');
                            const duration = userTime[day];
                            const left = (column * this.contentWidth()) / this.columns;
                            const total = 60 * 60 * this.workingHours;
                            const progress = duration / total;
                            const height = Math.ceil(Math.min(progress, 1) * (rowHeight - 1));
                            const color = this.getColor(progress);

                            const rect = draw
                                .rect(this.columnWidth(), height + 1)
                                .move(left, Math.floor(top + (rowHeight - height)) - 1)
                                .fill(color)
                                .stroke({ width: 0 })
                                .attr({
                                    cursor: cursor,
                                    hoverCursor: cursor,
                                });
                            squaresGroup.add(rect);

                            // Time label
                            draw.text(this.formatDuration(duration))
                                .move(this.columnWidth() / 2 + left, top + 22)
                                .size(this.columnWidth(), rowHeight)
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
                        draw.line(0, 0, this.contentWidth(), 0)
                            .move(0, top)
                            .stroke({ color: '#dfe5ed', width: 1 })
                            .attr({
                                cursor: cursor,
                                hoverCursor: cursor,
                            });
                    }
                });
            }, 100),
            onResize: debounce(function () {
                const canvasContainer = this.$refs.scrollArea;
                const width = canvasContainer.clientWidth;
                const height = this.users.length * rowHeight;
                this.draw.size(width, height);
                this.draw.viewbox(0, 0, width, height);
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
        user-select: none;
        height: 100%;
        position: relative;
        width: 100%;
    }
    .scrollbar-top {
        position: absolute;
        left: 0;
        top: -25px;
        width: 100%;
        height: 10px;
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
        overflow: auto;
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
