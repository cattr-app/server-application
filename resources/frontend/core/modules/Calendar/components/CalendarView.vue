<template>
    <div ref="container"></div>
</template>

<script>
    import { Svg, SVG } from '@svgdotjs/svg.js';
    import throttle from 'lodash/throttle';

    const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    const cellWidth = 100 / daysOfWeek.length;
    const cellHeight = 32;

    const maxTasks = 5;

    export default {
        props: {
            tasksByWeek: {
                type: Array,
                required: true,
            },
            showAll: {
                type: Boolean,
                default: true,
            },
        },
        created() {
            window.addEventListener('resize', this.resize);
        },
        mounted() {
            const { container } = this.$refs;
            this.svg = SVG().addTo(container);

            this.resize();
            this.draw();
        },
        destroyed() {
            window.removeEventListener('resize', this.resize);
        },
        watch: {
            tasksByWeek() {
                this.resize();
                this.draw();
            },
            showAll() {
                this.resize();
                this.draw();
            },
        },
        methods: {
            resize: throttle(function () {
                const { container } = this.$refs;

                const weeks = this.tasksByWeek.length;
                const tasks = this.tasksByWeek.reduce(
                    (acc, item) => acc + (this.showAll ? item.tasks.length : Math.min(maxTasks, item.tasks.length)),
                    0,
                );

                const rows = 1 + weeks + tasks;

                const width = container.clientWidth;
                const height = rows * cellHeight;

                this.svg.viewbox(0, 0, width, height);
            }, 100),
            draw: throttle(function () {
                /** @type {Svg} */
                const svg = this.svg;
                svg.clear();

                const group = svg.group();
                group.clipWith(svg.rect('100%', '100%').rx(20).ry(20));

                let horizontalOffset = 0;
                let verticalOffset = 0;

                const drawHeader = () => {
                    for (const day of daysOfWeek) {
                        const rect = group
                            .rect(`${cellWidth.toFixed(2)}%`, cellHeight)
                            .move(`${horizontalOffset.toFixed(2)}%`, verticalOffset)
                            .fill('#fff')
                            .stroke('#F4F4FF');

                        group
                            .text(add => add.tspan(this.$t(`calendar.days.${day}`)))
                            .font({ anchor: 'middle', size: 16 })
                            .amove(`${(horizontalOffset + cellWidth / 2).toFixed(2)}%`, verticalOffset + cellHeight / 2)
                            .fill('rgb(177, 177, 190)')
                            .clipWith(rect.clone());

                        horizontalOffset += cellWidth;
                    }

                    verticalOffset += cellHeight;
                };

                /**
                 * @param {string[]} days
                 */
                const drawDaysRow = days => {
                    horizontalOffset = 0;

                    for (const day of days) {
                        const rect = group
                            .rect(`${cellWidth.toFixed(2)}%`, cellHeight)
                            .move(`${horizontalOffset.toFixed(2)}%`, verticalOffset)
                            .fill('#fff')
                            .stroke('#F4F4FF');

                        group
                            .text(add => add.tspan(day.toString()).dmove(-8, 0))
                            .font({ anchor: 'end', size: 16 })
                            .amove(`${(horizontalOffset + cellWidth).toFixed(2)}%`, verticalOffset + cellHeight / 2)
                            .fill('rgb(63, 83, 110)')
                            .clipWith(rect.clone());

                        horizontalOffset += cellWidth;
                    }

                    verticalOffset += cellHeight;
                };

                /**
                 * @param {number} task_id
                 * @param {string} task_name
                 * @param {number} startWeekDay
                 * @param {number} endWeekDay
                 */
                const drawTaskRow = (task_id, task_name, startWeekDay, endWeekDay) => {
                    const width = cellWidth * (endWeekDay - startWeekDay + 1);
                    horizontalOffset = cellWidth * startWeekDay;

                    group.rect(`100%`, cellHeight).move(0, verticalOffset).fill('#fafafa').stroke('#F4F4FF');

                    const link = group.link(`/tasks/view/${task_id}`);
                    link.target('_blank');

                    const taskHorizontalPadding = 0.25;
                    const taskVerticaladding = 2;

                    const rect = link
                        .rect(`${width - 2 * taskHorizontalPadding}%`, cellHeight - 2 * taskVerticaladding)
                        .move(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + taskVerticaladding)
                        .fill('#fff')
                        .stroke('#F4F4FF');

                    link.text(add => add.tspan(task_name).dmove(8, 0))
                        .font({ anchor: 'start', size: 16 })
                        .amove(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + cellHeight / 2)
                        .fill('rgb(63, 83, 110)')
                        .clipWith(rect.clone());

                    verticalOffset += cellHeight;
                };

                drawHeader();

                for (const { days, tasks } of this.tasksByWeek) {
                    drawDaysRow(days);

                    for (const {
                        task: { id, task_name },
                        start_week_day,
                        end_week_day,
                    } of this.showAll ? tasks : tasks.slice(0, maxTasks)) {
                        drawTaskRow(id, task_name, start_week_day, end_week_day);
                    }
                }
            }, 100),
        },
    };
</script>
