<template>
    <div class="calendar">
        <div ref="container" class="calendar__svg"></div>

        <div
            v-show="hoverPopup.show"
            :style="{
                left: `${hoverPopup.x}px`,
                top: `${hoverPopup.y}px`,
            }"
            class="calendar__popup popup"
        >
            <template v-if="hoverPopup.task">
                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.name') }}</span>
                    <span class="popup__value">{{ hoverPopup.task.task_name }}</span>
                </p>

                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.status') }}</span>
                    <span class="popup__value">{{ hoverPopup.task.status.name }}</span>
                </p>

                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.priority') }}</span>
                    <span class="popup__value">{{ hoverPopup.task.priority.name }}</span>
                </p>

                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.estimate') }}</span>
                    <span class="popup__value">{{ formatDuration(hoverPopup.task.estimate) }}</span>
                </p>

                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.total_spent_time') }}</span>
                    <span class="popup__value">{{ formatDuration(hoverPopup.task.total_spent_time) }}</span>
                </p>

                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.start_date') }}</span>
                    <span class="popup__value">{{ formatDate(hoverPopup.task.start_date) }}</span>
                </p>

                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.due_date') }}</span>
                    <span class="popup__value">{{ formatDate(hoverPopup.task.due_date) }}</span>
                </p>

                <p class="popup__row">
                    <span class="popup__key">{{ $t('calendar.task.forecast_completion_date') }}</span>
                    <span class="popup__value">{{ formatDate(hoverPopup.task.forecast_completion_date) }}</span>
                </p>
            </template>
        </div>
    </div>
</template>

<script>
    import { Svg, SVG } from '@svgdotjs/svg.js';
    import { formatDurationString } from '@/utils/time';
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
        data() {
            return {
                hoverPopup: {
                    show: false,
                    x: 0,
                    y: 0,
                    task: null,
                },
            };
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
            formatDuration(value) {
                return value !== null ? formatDurationString(value) : '—';
            },
            formatDate(value) {
                return value !== null ? value : '—';
            },
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

                    group.line(0, verticalOffset, '100%', verticalOffset).stroke({ width: 1, color: '#C5D9E8' });
                    group
                        .line(0, verticalOffset + cellHeight - 1, '100%', verticalOffset + cellHeight - 1)
                        .stroke({ width: 1, color: '#C5D9E8' });

                    verticalOffset += cellHeight;
                };

                /**
                 * @param {Object} task
                 * @param {number} startWeekDay
                 * @param {number} endWeekDay
                 */
                const drawTaskRow = (task, startWeekDay, endWeekDay) => {
                    const width = cellWidth * (endWeekDay - startWeekDay + 1);
                    horizontalOffset = cellWidth * startWeekDay;

                    group.rect(`100%`, cellHeight).move(0, verticalOffset).fill(backgroundColor).stroke(borderColor);

                    const taskHorizontalPadding = 0.2;
                    const taskVerticalPadding = 3;

                    const popupWidth = 420;
                    const popupHeight = 220;
                    const onClick = () => {
                        this.$router.push(`/tasks/view/${task.id}`);
                    };
                    const onMouseOver = event => {
                        const rectBBox = rect.bbox();
                        const popupX =
                            event.clientX < this.$refs.container.clientWidth - popupWidth
                                ? event.clientX
                                : event.clientX - popupWidth - 40;

                        const popupY =
                            rectBBox.y + this.$refs.container.getBoundingClientRect().y <
                            window.innerHeight - popupHeight
                                ? rectBBox.y
                                : rectBBox.y - popupHeight;

                        this.hoverPopup = {
                            show: true,
                            x: popupX,
                            y: popupY,
                            task,
                        };
                    };

                    const onMouseOut = event => {
                        this.hoverPopup = {
                            ...this.hoverPopup,
                            show: false,
                        };
                    };

                    const rect = group
                        .rect(`${width - 2 * taskHorizontalPadding}%`, cellHeight - 2 * taskVerticalPadding)
                        .move(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + taskVerticalPadding)
                        .fill(blockColor)
                        .stroke(borderColor)
                        .on('mouseover', event => {
                            onMouseOver(event);
                            event.target.style.cursor = 'pointer';
                        })
                        .on('mouseout', onMouseOut)
                        .on('click', onClick);

                    let pxOffset = 0;
                    if (new Date(task.due_date).getTime() + msInDay < new Date().getTime()) {
                        pxOffset += 2 * taskVerticalPadding;
                        group
                            .rect(cellHeight - 4 * taskVerticalPadding, cellHeight - 4 * taskVerticalPadding)
                            .move(
                                `${horizontalOffset + taskHorizontalPadding}%`,
                                verticalOffset + 2 * taskVerticalPadding,
                            )
                            .transform({ translateX: pxOffset })
                            .fill('#FF5569')
                            .stroke(borderColor)
                            .rx(4)
                            .ry(4)
                            .on('mouseover', event => {
                                onMouseOver(event);
                                event.target.style.cursor = 'pointer';
                            })
                            .on('mouseout', onMouseOut)
                            .on('click', onClick);

                        pxOffset += cellHeight - 4 * taskVerticalPadding;
                    }

                    if (task.estimate !== null && Number(task.total_spent_time) > Number(task.estimate)) {
                        pxOffset += 2 * taskVerticalPadding;
                        group
                            .rect(cellHeight - 4 * taskVerticalPadding, cellHeight - 4 * taskVerticalPadding)
                            .move(
                                `${horizontalOffset + taskHorizontalPadding}%`,
                                verticalOffset + 2 * taskVerticalPadding,
                            )
                            .transform({ translateX: pxOffset })
                            .fill('#FFC82C')
                            .stroke(borderColor)
                            .rx(4)
                            .ry(4)
                            .on('mouseover', event => {
                                onMouseOver(event);
                                event.target.style.cursor = 'pointer';
                            })
                            .on('mouseout', onMouseOut)
                            .on('click', onClick);

                        pxOffset += cellHeight - 4 * taskVerticalPadding;
                    }

                    group
                        .text(add => add.tspan(task.task_name).dmove(8, 0))
                        .font({ anchor: 'start', size: 16 })
                        .amove(`${horizontalOffset + taskHorizontalPadding}%`, verticalOffset + cellHeight / 2)
                        .transform({ translateX: pxOffset })
                        .fill(textColor)
                        .clipWith(rect.clone())
                        .on('mouseover', event => {
                            onMouseOver(event);
                            event.target.style.cursor = 'pointer';
                        })
                        .on('mouseout', onMouseOut)
                        .on('click', onClick);
                    verticalOffset += cellHeight;
                };

                drawHeader();

                for (const { days, tasks } of this.tasksByWeek) {
                    drawDaysRow(days);

                    for (const { task, start_week_day, end_week_day } of this.showAll
                        ? tasks
                        : tasks.slice(0, maxTasks)) {
                        drawTaskRow(task, start_week_day, end_week_day);
                    }
                }
            }, 100),
        },
    };
</script>

<style lang="scss" scoped>
    .calendar {
        display: flex;

        align-items: center;
        justify-content: center;

        position: relative;

        &__svg {
            width: 100%;
            height: 100%;
        }

        &__popup {
            background: #ffffff;
            border-radius: 20px;
            border: 0;
            box-shadow: 0px 7px 64px rgba(0, 0, 0, 0.07);

            position: absolute;
            display: block;
            padding: 10px;
            width: 100%;
            max-width: 420px;

            pointer-events: none;

            z-index: 1;
        }
    }

    .popup {
        &__row {
            display: flex;
            justify-content: space-between;
        }

        &__value {
            font-weight: bold;
            text-align: right;
        }
    }
</style>
