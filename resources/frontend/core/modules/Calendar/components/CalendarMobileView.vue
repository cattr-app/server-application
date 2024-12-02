<template>
    <div ref="container"></div>
</template>

<script>
    import { Svg, SVG } from '@svgdotjs/svg.js';
    import throttle from 'lodash/throttle';

    const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    const cellWidth = 100 / daysOfWeek.length;
    const cellHeight = 32;

    export default {
        props: {
            tasksByDay: {
                type: Array,
                required: true,
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
            tasksByDay() {
                this.resize();
                this.draw();
            },
        },
        methods: {
            resize: throttle(function () {
                const { container } = this.$refs;

                const weeks = Math.ceil(this.tasksByDay.length / 7);
                const rows = 1 + 2 * weeks;

                const width = container.clientWidth;
                const height = rows * cellHeight;

                this.svg.viewbox(0, 0, width, height);
            }, 100),
            draw: throttle(function () {
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

                const drawDays = () => {
                    horizontalOffset = 0;

                    const days = this.tasksByDay;
                    for (let i = 0; i < days.length; i++) {
                        const { date, day, tasks } = days[i];

                        const onClick = () => this.$emit('show-tasks-modal', { date, tasks });

                        const rect = group
                            .rect(`${cellWidth.toFixed(2)}%`, 2 * cellHeight)
                            .move(`${horizontalOffset.toFixed(2)}%`, verticalOffset)
                            .fill(blockColor)
                            .stroke(borderColor)
                            .on('click', onClick);

                        const text = group
                            .text(add => add.tspan(day.toString()))
                            .font({ anchor: 'middle', size: 16 })
                            .amove(`${(horizontalOffset + cellWidth / 2).toFixed(2)}%`, verticalOffset + cellHeight / 2)
                            .fill(textColor)
                            .clipWith(rect.clone())
                            .on('click', onClick);

                        if (tasks.length > 0) {
                            rect.attr('cursor', 'pointer');
                            text.attr('cursor', 'pointer');
                            group
                                .circle(10)
                                .attr('cx', `${(horizontalOffset + cellWidth / 2).toFixed(2)}%`)
                                .attr('cy', verticalOffset + cellHeight * 1.5)
                                .fill('rgb(177, 177, 190)')
                                .on('click', onClick)
                                .attr('cursor', 'pointer');
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
            }, 100),
        },
    };
</script>
