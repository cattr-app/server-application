<template>
    <div>
        <div ref="container" class="svg-container svg-container__desktop"></div>
        <div ref="mobile-container" class="svg-container svg-container__mobile"></div>
    </div>
</template>

<script>
    import { Svg, SVG } from '@svgdotjs/svg.js';
    import moment from 'moment';
    import CalendarService from '../services/calendar.service';

    const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    const cellWidth = 100 / daysOfWeek.length;
    const cellHeight = 32;

    const maxWidth = 1200;

    export default {
        data() {
            return {
                tasks: {},
                tasksByDay: {},
                tasksByWeek: {},
            };
        },
        created() {
            this.service = new CalendarService();
            this.service
                .get(moment().startOf('month').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD'))
                .then(response => {
                    this.tasks = response.data.data.tasks;
                    this.tasksByDay = response.data.data.tasks_by_day;
                    this.tasksByWeek = response.data.data.tasks_by_week;

                    this.resize();
                    this.draw();
                });

            window.addEventListener('resize', this.resize);
        },
        mounted() {
            const { container, 'mobile-container': mobileContainer } = this.$refs;
            this.svg = SVG().addTo(container);
            this.mobileSvg = SVG().addTo(mobileContainer);

            this.resize();
            this.draw();
        },
        destroyed() {
            window.removeEventListener('resize', this.resize);
        },
        methods: {
            resize() {
                this.resizeMobile();
                this.resizeDesktop();
            },
            resizeMobile() {
                const { 'mobile-container': mobileContainer } = this.$refs;

                const weeks = Object.keys(this.tasksByWeek).length;
                const rows = 1 + 2 * weeks;

                const width = Math.min(maxWidth, mobileContainer.clientWidth);
                const height = rows * cellHeight;

                this.mobileSvg.viewbox(0, 0, width, height);
            },
            resizeDesktop() {
                const { container } = this.$refs;

                const weeks = Object.keys(this.tasksByWeek).length;
                const tasks = Object.values(this.tasksByWeek).reduce((acc, item) => acc + item.tasks.length, 0);
                const rows = 1 + weeks + tasks;

                const width = Math.min(maxWidth, container.clientWidth);
                const height = rows * cellHeight;

                this.svg.viewbox(0, 0, width, height);
            },
            draw() {
                this.drawMobile();
                this.drawDesktop();
            },
            drawMobile() {
                /** @type {Svg} */
                const svg = this.mobileSvg;
                svg.clear();

                const group = svg.group();
                group.clipWith(svg.rect('100%', '100%').rx(20).ry(20));

                let horizontalOffset = 0;
                let verticalOffset = 0;

                const showTasksModal = tasks => {
                    // TODO: show modal
                    console.log(tasks);
                };

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

                const drawDays = () => {
                    horizontalOffset = 0;

                    const days = Object.values(this.tasksByDay);
                    for (let i = 0; i < days.length; i++) {
                        const { day, task_ids: taskIds } = days[i];

                        const onClick = () => showTasksModal(taskIds.map(taskId => this.tasks[taskId]));

                        const rect = group
                            .rect(`${cellWidth.toFixed(2)}%`, 2 * cellHeight)
                            .move(`${horizontalOffset.toFixed(2)}%`, verticalOffset)
                            .fill('#fff')
                            .stroke('#F4F4FF')
                            .on('click', onClick);

                        group
                            .text(add => add.tspan(day.toString()))
                            .font({ anchor: 'middle', size: 16 })
                            .amove(`${(horizontalOffset + cellWidth / 2).toFixed(2)}%`, verticalOffset + cellHeight / 2)
                            .fill('rgb(63, 83, 110)')
                            .clipWith(rect.clone())
                            .on('click', onClick);

                        if (taskIds.length > 0) {
                            group
                                .circle(10)
                                .attr('cx', `${(horizontalOffset + cellWidth / 2).toFixed(2)}%`)
                                .attr('cy', verticalOffset + cellHeight * 1.5)
                                .fill('rgb(177, 177, 190)')
                                .on('click', onClick);
                        }

                        if (i % daysOfWeek.length === daysOfWeek.length - 1) {
                            horizontalOffset = 0;
                            verticalOffset += 2 * cellHeight;
                        } else {
                            horizontalOffset += cellWidth;
                        }
                    }
                };

                drawHeader();
                drawDays();
            },
            drawDesktop() {
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
                 * @param {number} taskId
                 * @param {number} startWeekDay
                 * @param {number} endWeekDay
                 */
                const drawTaskRow = (taskId, startWeekDay, endWeekDay) => {
                    const width = cellWidth * (endWeekDay - startWeekDay + 1);
                    horizontalOffset = cellWidth * startWeekDay;

                    group.rect(`100%`, cellHeight).move(0, verticalOffset).fill('#fafafa').stroke('#F4F4FF');

                    const link = group.link(`/tasks/view/${taskId}`);
                    link.target('_blank');

                    const taskHorizontalPadding = 0.25;
                    const taskVerticaladding = 2;

                    const rect = link
                        .rect(`${width - 2 * taskHorizontalPadding}%`, cellHeight - 2 * taskVerticaladding)
                        .move(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + taskVerticaladding)
                        .fill('#fff')
                        .stroke('#F4F4FF');

                    link.text(add => add.tspan(this.tasks[taskId].task_name).dmove(8, 0))
                        .font({ anchor: 'start', size: 16 })
                        .amove(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + cellHeight / 2)
                        .fill('rgb(63, 83, 110)')
                        .clipWith(rect.clone());

                    verticalOffset += cellHeight;
                };

                drawHeader();

                for (const { days, tasks } of Object.values(this.tasksByWeek)) {
                    drawDaysRow(days);

                    for (const { task_id, start_week_day, end_week_day } of tasks) {
                        drawTaskRow(task_id, start_week_day, end_week_day);
                    }
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .svg-container {
        align-items: center;
        justify-content: center;

        &__mobile {
            display: flex;

            @media screen and (min-width: 768px) {
                display: none;
            }
        }

        &__desktop {
            display: none;

            @media screen and (min-width: 768px) {
                display: flex;
            }
        }

        &::v-deep svg {
            border: 1px solid #f4f4ff;
            border-radius: 20px;

            box-sizing: border-box;
            box-shadow: 0px 0px 100px rgba(63, 51, 86, 0.05);

            width: 100%;
            height: 100%;

            max-width: 1200px;
        }

        &::v-deep text {
            dominant-baseline: central;
        }
    }
</style>
