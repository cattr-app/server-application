<template>
    <div ref="container" class="svg-container"></div>
</template>

<script>
    import { SVG } from '@svgdotjs/svg.js';
    import moment from 'moment';
    import CalendarService from '../services/calendar.service';

    const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const cellWidth = 100 / daysOfWeek.length;
    const cellHeight = 32;

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
            const { container } = this.$refs;
            this.svg = SVG().addTo(container);

            this.resize();
            this.draw();
        },
        destroyed() {
            window.removeEventListener('resize', this.resize);
        },
        methods: {
            resize() {
                const { container } = this.$refs;

                const rows =
                    1 +
                    Object.keys(this.tasksByWeek).length +
                    Object.values(this.tasksByWeek).reduce((acc, item) => acc + item.tasks.length, 0);

                this.svg.viewbox(0, 0, container.clientWidth, rows * cellHeight);
            },
            draw() {
                this.svg.clear();
                let horizontalOffset = 0;
                let verticalOffset = 0;

                const group = this.svg.group();
                group.clipWith(this.svg.rect('100%', '100%').rx(20).ry(20));

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

                for (const { days, tasks } of Object.values(this.tasksByWeek)) {
                    horizontalOffset = 0;

                    for (const day of days) {
                        const rect = group
                            .rect(`${cellWidth.toFixed(2)}%`, cellHeight)
                            .move(`${horizontalOffset.toFixed(2)}%`, verticalOffset)
                            .fill('#fff')
                            .stroke('#F4F4FF');

                        group
                            .text(add => add.tspan(day).dmove(-8, 0))
                            .font({ anchor: 'end', size: 16 })
                            .amove(`${(horizontalOffset + cellWidth).toFixed(2)}%`, verticalOffset + cellHeight / 2)
                            .fill('rgb(63, 83, 110)')
                            .clipWith(rect.clone());

                        horizontalOffset += cellWidth;
                    }

                    verticalOffset += cellHeight;

                    for (const { task_id, start_week_day, end_week_day } of tasks) {
                        const width = cellWidth * (end_week_day - start_week_day + 1);
                        horizontalOffset = cellWidth * start_week_day;

                        group.rect(`100%`, cellHeight).move(0, verticalOffset).fill('#fafafa').stroke('#F4F4FF');

                        const rect = group
                            .rect(`${width}%`, cellHeight)
                            .move(`${horizontalOffset}%`, verticalOffset)
                            .fill('#fff')
                            .stroke('#F4F4FF');

                        group
                            .text(add => add.tspan(this.tasks[task_id].task_name).dmove(8, 0))
                            .font({ anchor: 'start', size: 16 })
                            .amove(`${horizontalOffset}%`, verticalOffset + cellHeight / 2)
                            .fill('rgb(63, 83, 110)')
                            .clipWith(rect.clone());

                        verticalOffset += cellHeight;
                    }
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .svg-container {
        display: flex;
        align-items: center;
        justify-content: center;

        &::v-deep svg {
            border: 1px solid #f4f4ff;
            border-radius: 20px;

            box-sizing: border-box;
            box-shadow: 0px 0px 100px rgba(63, 51, 86, 0.05);

            width: 100%;
            height: 100%;
        }

        &::v-deep text {
            dominant-baseline: central;
        }
    }
</style>
