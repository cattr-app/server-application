<template>
    <div ref="canvas" class="canvas" @pointerdown="onDown">
        <div ref="scrollbarTop" class="scrollbar-top" @scroll="onScroll">
            <div :style="{ width: `${totalWidth}px` }" />
        </div>
    </div>
</template>

<script>
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
                lastPosX: 0,
                offsetX: 0,
                totalWidth: 0,
                scrollPos: 0,
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
        },
        mounted() {
            this.draw = SVG();
            this.onResize();
            window.addEventListener('resize', this.onResize);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
        },
        methods: {
            height() {
                return this.users.length * rowHeight;
            },
            canvasWidth() {
                return this.$refs.canvas.clientWidth;
            },
            columnWidth() {
                return Math.max(minColumnWidth, this.canvasWidth() / this.columns);
            },
            async contentWidth() {
                await this.$nextTick();
                this.totalWidth = this.columns * this.columnWidth();
                return this.totalWidth;
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
            formatDuration: formatDurationString,
            drawGrid: async function () {
                if (typeof this.draw === 'undefined') return;
                this.draw.clear();
                const draw = this.draw;
                const canvasContainer = this.$refs.canvas;
                const width = canvasContainer.clientWidth;
                const columnWidth = this.columnWidth();
                const height = this.height();
                if (height <= 0) {
                    return;
                }
                const start = moment(this.start, 'YYYY-MM-DD');

                const cursor = (await this.contentWidth()) > this.canvasWidth() ? 'move' : 'default';
                draw.addTo(canvasContainer).size(width, height + titleHeight + subtitleHeight);
                draw.viewbox(0, 20, width, height);
                // Background
                draw.rect((await this.contentWidth()) - 1, height - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .fill('#fafafa')
                    .stroke({ color: '#dfe5ed', width: 1 })
                    .attr({
                        cursor: cursor,
                        hoverCursor: cursor,
                    })
                    .on('mousedown', () => this.$emit('outsideClick'));
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
                        draw.line(0, titleHeight + subtitleHeight, 0, height + titleHeight + subtitleHeight)
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
                    .rect((await this.contentWidth()) - 1, height - 1)
                    .move(0, titleHeight + subtitleHeight)
                    .radius(20)
                    .attr({
                        absolutePositioned: true,
                    });
                const squaresGroup = draw.group().clipWith(clipPath);
                for (const [row, user] of this.users.entries()) {
                    const top = row * rowHeight + titleHeight + subtitleHeight;
                    const userTime = filteredData[user.id];

                    if (userTime) {
                        for (const day of Object.keys(userTime)) {
                            const column = -start.diff(day, 'days');
                            const duration = userTime[day];
                            const left = (column * (await this.contentWidth())) / this.columns;
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
                        }
                    }
                    // Horizontal grid lines
                    if (row > 0) {
                        draw.line(0, 0, await this.contentWidth(), 0)
                            .move(0, top)
                            .stroke({ color: '#dfe5ed', width: 1 })
                            .attr({
                                cursor: cursor,
                                hoverCursor: cursor,
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
        },
        watch: {
            start() {
                this.setScroll(0);
            },
            end() {
                this.setScroll(0);
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
    .canvas {
        user-select: none;
        touch-action: pan-y;
        height: 100%;
        position: relative;
        width: 100%;
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
    @media (max-width: 720px) {
        .canvas {
            .scrollbar-top {
                top: -1rem;
            }
        }
    }
</style>
