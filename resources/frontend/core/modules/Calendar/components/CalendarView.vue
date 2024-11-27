<template>
    <div ref="container"></div>
</template>

<script>
    import { Svg, SVG } from '@svgdotjs/svg.js';
    import throttle from 'lodash/throttle';

    const msInDay = 24 * 60 * 60 * 1000;
    const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const months = [
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december',
    ];

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
                const backgroundColor = '#fafafa';
                const borderColor = '#eeeef5';
                const blockColor = '#fff';
                const textColor = 'rgb(63, 83, 110)';

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
                            .fill(blockColor)
                            .stroke(borderColor);

                        group
                            .text(add => add.tspan(this.$t(`calendar.days.${day}`)))
                            .font({ anchor: 'middle', size: 16 })
                            .amove(`${(horizontalOffset + cellWidth / 2).toFixed(2)}%`, verticalOffset + cellHeight / 2)
                            .fill(textColor)
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

                    for (let i = 0; i < days.length; i++) {
                        const { month, day } = days[i];
                        const rect = group
                            .rect(`${cellWidth.toFixed(2)}%`, cellHeight)
                            .move(`${horizontalOffset.toFixed(2)}%`, verticalOffset)
                            .fill(i < 5 ? blockColor : backgroundColor)
                            .stroke(borderColor);

                        const dayText =
                            day === 1 ? `${this.$t(`calendar.months.${months[month - 1]}`)} ${day}` : day.toString();
                        group
                            .text(add => add.tspan(dayText).dmove(-8, 0))
                            .font({ anchor: 'end', size: 16 })
                            .amove(`${(horizontalOffset + cellWidth).toFixed(2)}%`, verticalOffset + cellHeight / 2)
                            .fill(textColor)
                            .clipWith(rect.clone());

                        horizontalOffset += cellWidth;
                    }

                    verticalOffset += cellHeight;
                };

                /**
                 * @param {number} taskId
                 * @param {string} taskName
                 * @param {number} estimate
                 * @param {number} totalSpentTime
                 * @param {string} dueDate
                 * @param {number} startWeekDay
                 * @param {number} endWeekDay
                 */
                const drawTaskRow = (taskId, taskName, estimate, totalSpentTime, dueDate, startWeekDay, endWeekDay) => {
                    const width = cellWidth * (endWeekDay - startWeekDay + 1);
                    horizontalOffset = cellWidth * startWeekDay;

                    group.rect(`100%`, cellHeight).move(0, verticalOffset).fill(backgroundColor).stroke(borderColor);

                    const link = group.link(`/tasks/view/${taskId}`);

                    const taskHorizontalPadding = 0.2;
                    const taskVerticalPadding = 3;

                    const rect = link
                        .rect(`${width - 2 * taskHorizontalPadding}%`, cellHeight - 2 * taskVerticalPadding)
                        .move(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + taskVerticalPadding)
                        .fill(blockColor)
                        .stroke(borderColor);

                    let pxOffset = 0;
                    if (new Date(dueDate).getTime() + msInDay < new Date().getTime()) {
                        pxOffset += 2 * taskVerticalPadding;
                        link.rect(cellHeight - 4 * taskVerticalPadding, cellHeight - 4 * taskVerticalPadding)
                            .move(
                                `${horizontalOffset + taskHorizontalPadding}%`,
                                verticalOffset + 2 * taskVerticalPadding,
                            )
                            .transform({ translateX: pxOffset })
                            .fill('#FF5569')
                            .stroke(borderColor)
                            .rx(4)
                            .ry(4);

                        pxOffset += cellHeight - 4 * taskVerticalPadding;
                    }

                    if (estimate !== null && totalSpentTime > estimate) {
                        pxOffset += 2 * taskVerticalPadding;
                        link.rect(cellHeight - 4 * taskVerticalPadding, cellHeight - 4 * taskVerticalPadding)
                            .move(
                                `${horizontalOffset + taskHorizontalPadding}%`,
                                verticalOffset + 2 * taskVerticalPadding,
                            )
                            .transform({ translateX: pxOffset })
                            .fill('#FFC82C')
                            .stroke(borderColor)
                            .rx(4)
                            .ry(4);

                        pxOffset += cellHeight - 4 * taskVerticalPadding;
                    }

                    link.text(add => add.tspan(taskName).dmove(8, 0))
                        .font({ anchor: 'start', size: 16 })
                        .amove(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + cellHeight / 2)
                        .transform({ translateX: pxOffset })
                        .fill(textColor)
                        .clipWith(rect.clone());

                    verticalOffset += cellHeight;
                };

                drawHeader();

                for (const { days, tasks } of this.tasksByWeek) {
                    drawDaysRow(days);

                    for (const {
                        task: { id, task_name, estimate, total_spent_time, due_date },
                        start_week_day,
                        end_week_day,
                    } of this.showAll ? tasks : tasks.slice(0, maxTasks)) {
                        drawTaskRow(
                            id,
                            task_name,
                            estimate,
                            Number(total_spent_time),
                            due_date,
                            start_week_day,
                            end_week_day,
                        );
                    }
                }
            }, 100),
        },
    };
</script>
