<template>
    <div class="gantt">
        <div class="row flex-end">
            <at-button size="large" @click="$router.go(-1)">{{ $t('control.back') }}</at-button>
        </div>
        <v-chart ref="gantt" class="gantt__chart" />
        <preloader v-if="isDataLoading" :is-transparent="true" class="gantt__loader" />
    </div>
</template>

<script>
    import i18n from '@/i18n';

    const HEIGHT_RATIO = 0.6;
    const ROW_HEIGHT = 20;
    const rawDimensions = [
        'index',
        'id',
        'task_name',
        'priority_id',
        'status_id',
        'estimate',
        'start_date',
        'due_date',
        'project_phase_id',
        'project_id',
        'total_spent_time',
        'total_offset',
        'status',
        'priority',
    ];
    const i18nDimensions = rawDimensions.map(t => i18n.t(`gantt.dimensions.${t}`));

    const dimensionIndex = Object.fromEntries(rawDimensions.map((el, i) => [el, i]));

    const dimensionsMap = new Map(rawDimensions.map((el, i) => [i, el]));

    const rawPhaseDimensions = ['id', 'name', 'start_date', 'due_date', 'first_task_id', 'last_task_id'];
    const i18nPhaseDimensions = rawPhaseDimensions.map(t => i18n.t(`gantt.dimensions.${t}`));
    const phaseDimensionIndex = Object.fromEntries(rawPhaseDimensions.map((el, i) => [el, i]));

    import { use, format as echartsFormat, graphic as echartsGraphic } from 'echarts/core';
    import { CanvasRenderer } from 'echarts/renderers';
    import { PieChart } from 'echarts/charts';
    import { CustomChart } from 'echarts/charts';

    import {
        LegendComponent,
        TooltipComponent,
        ToolboxComponent,
        TitleComponent,
        DataZoomComponent,
        GridComponent,
    } from 'echarts/components';
    import VChart, { THEME_KEY } from 'vue-echarts';
    import debounce from 'lodash/debounce';
    import Preloader from '@/components/Preloader.vue';
    import GanttService from '@/services/resource/gantt.service';
    import { formatDurationString, getStartDate } from '@/utils/time';
    import moment from 'moment-timezone';
    import { mapGetters } from 'vuex';

    use([
        CanvasRenderer,
        PieChart,
        TitleComponent,
        TooltipComponent,
        LegendComponent,
        TooltipComponent,
        ToolboxComponent,
        TitleComponent,
        DataZoomComponent,
        GridComponent,
        CustomChart,
        CanvasRenderer,
    ]);

    const grid = {
        show: true,
        top: 70,
        bottom: 20,
        left: 100,
        right: 20,
        backgroundColor: '#fff',
        borderWidth: 0,
    };

    export default {
        name: 'Index',
        components: {
            Preloader,
            VChart,
        },
        provide: {
            // [THEME_KEY]: 'dark',
        },
        data() {
            return {
                isDataLoading: false,
                service: new GanttService(),
                option: {},
                tasksRelationsMap: [],
                totalRows: 0,
            };
        },
        async created() {
            await this.load();
        },
        mounted() {
            window.addEventListener('resize', this.onResize);
            this.websocketEnterChannel(this.user.id, {
                updateAll: data => {
                    const id = this.$route.params[this.service.getIdParam()];
                    if (+id === +data.model.id) {
                        this.prepareAndSetData(data.model);
                    }
                },
            });

            this.$refs.gantt.chart.on('click', { element: 'got_to_task_btn' }, params => {
                this.$router.push({
                    name: 'Tasks.crud.tasks.view',
                    params: { id: params.data[dimensionIndex['id']] },
                });
            });
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
            this.websocketLeaveChannel(this.user.id);
        },
        computed: {
            ...mapGetters('user', ['user']),
        },
        methods: {
            getYAxisZoomPercentage() {
                const chartHeight = this.$refs.gantt.chart.getHeight();
                const canDraw = chartHeight / (ROW_HEIGHT * this.totalRows * 2); // multiply by 2 so rows not squashed together
                return canDraw * 100;
            },
            onResize: debounce(
                function () {
                    this.$refs.gantt.chart.resize();
                    this.$refs.gantt.chart.setOption({
                        dataZoom: { id: 'sliderY', start: 0, end: this.getYAxisZoomPercentage() },
                    });
                },
                50,
                {
                    maxWait: 100,
                },
            ),
            load: debounce(async function () {
                this.isDataLoading = true;

                const ganttData = (await this.service.getGanttData(this.$route.params.id)).data.data;

                this.prepareAndSetData(ganttData);

                this.isDataLoading = false;
            }, 100),
            prepareAndSetData(ganttData) {
                this.totalRows = ganttData.tasks.length;

                const phasesMap = ganttData.phases
                    .filter(p => p.start_date && p.due_date)
                    .reduce((acc, phase) => {
                        phase.tasks = {
                            byStartDate: {},
                            byDueDate: {},
                        };
                        acc[phase.id] = phase;
                        return acc;
                    }, {});

                const preparedRowsMap = {};
                const preparedRows = ganttData.tasks.map((item, index) => {
                    const row = [index + 1].concat(...Object.values(item));
                    preparedRowsMap[item.id] = row;
                    if (phasesMap[item.project_phase_id]) {
                        const phaseTasks = phasesMap[item.project_phase_id].tasks;
                        if (phaseTasks.byStartDate[item.start_date]) {
                            phaseTasks.byStartDate[item.start_date].push(row);
                        } else {
                            phaseTasks.byStartDate[item.start_date] = [row];
                        }
                        if (phaseTasks.byDueDate[item.due_date]) {
                            phaseTasks.byDueDate[item.due_date].push(row);
                        } else {
                            phaseTasks.byDueDate[item.due_date] = [row];
                        }
                    }
                    return preparedRowsMap[item.id];
                });

                this.tasksRelationsMap = ganttData.tasks_relations.reduce((obj, relation) => {
                    const child = preparedRowsMap[relation.child_id];
                    if (Array.isArray(obj[relation.parent_id]) && child) {
                        obj[relation.parent_id].push(child);
                    } else {
                        obj[relation.parent_id] = [child];
                    }

                    return obj;
                }, {});

                const option = {
                    animation: false,
                    toolbox: {
                        left: 20,
                        top: 0,
                        itemSize: 20,
                    },
                    title: {
                        text: `${ganttData.name}`,
                        left: '4',
                        textAlign: 'left',
                    },
                    dataZoom: [
                        {
                            type: 'slider',
                            xAxisIndex: 0,
                            filterMode: 'none',
                            height: 20,
                            bottom: 0,
                            start: 0,
                            end: 100,
                            handleIcon:
                                'path://M10.7,11.9H9.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4h1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
                            handleSize: '80%',
                            showDetail: true,
                            labelFormatter: getStartDate,
                        },
                        {
                            type: 'inside',
                            id: 'insideX',
                            xAxisIndex: 0,
                            filterMode: 'none',
                            start: 0,
                            end: 50,
                            zoomOnMouseWheel: false,
                            moveOnMouseMove: true,
                        },
                        {
                            type: 'slider',
                            id: 'sliderY',
                            filterMode: 'none',
                            yAxisIndex: 0,
                            width: 10,
                            right: 10,
                            top: 70,
                            bottom: 20,
                            start: 0,
                            end: this.getYAxisZoomPercentage(this.totalRows),
                            handleSize: 0,
                            showDetail: false,
                        },
                        {
                            type: 'inside',
                            id: 'insideY',
                            yAxisIndex: 0,
                            filterMode: 'none',
                            // startValue: 0,
                            // endValue: 10,
                            zoomOnMouseWheel: 'shift',
                            moveOnMouseMove: true,
                            moveOnMouseWheel: true,
                        },
                    ],
                    grid,
                    xAxis: {
                        type: 'time',
                        position: 'top',
                        splitLine: {
                            lineStyle: {
                                color: ['#E9EDFF'],
                            },
                        },
                        axisLine: {
                            show: false,
                        },
                        axisTick: {
                            lineStyle: {
                                color: '#929ABA',
                            },
                        },
                        axisLabel: {
                            color: '#929ABA',
                            inside: false,
                            align: 'center',
                        },
                    },
                    yAxis: {
                        axisTick: {
                            show: false,
                        },
                        splitLine: {
                            show: false,
                        },
                        axisLine: {
                            show: false,
                        },
                        axisLabel: {
                            show: false,
                        },
                        inverse: true,
                        // axisPointer: {
                        //     show: true,
                        //     type: 'line',
                        //     data: [
                        //         [40, -10],
                        //         [-30, -5],
                        //         [-76.5, 20],
                        //         [-63.5, 40],
                        //         [-22.1, 50],
                        //     ]
                        // },
                        max: this.totalRows + 1,
                    },
                    tooltip: {
                        textStyle: {},
                        formatter: function (params) {
                            const getRow = (key, value) => `
                            <div style="display: inline-flex; width: 100%; justify-content: space-between; column-gap: 1rem; text-overflow: ellipsis;">
                            ${key} <span style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" ><b>${value}</b></span>
                            </div>`;
                            const getWrapper = dimensionsToShow => `
                                <div style="display:flex; flex-direction: column; max-width: 280px;">
                                    ${dimensionsToShow.map(([title, value]) => getRow(title, value)).join('')}
                                </div>
                                `;
                            const prepareValues = accessor => {
                                const key = params.dimensionNames[Array.isArray(accessor) ? accessor[0] : accessor];
                                let value = Array.isArray(accessor)
                                    ? accessor[1](params.value[accessor[0]])
                                    : params.value[accessor];
                                return [key, value];
                            };
                            if (params.seriesId === 'tasksData' || params.seriesId === 'tasksLabels') {
                                return getWrapper([
                                    prepareValues(dimensionIndex.task_name),
                                    prepareValues([dimensionIndex.status, v => v.name]),
                                    prepareValues([dimensionIndex.priority, v => v.name]),
                                    prepareValues([
                                        dimensionIndex.estimate,
                                        v => (v == null ? '—' : formatDurationString(v)),
                                    ]),
                                    prepareValues([
                                        dimensionIndex.total_spent_time,
                                        v => (v == null ? '—' : formatDurationString(v)),
                                    ]),
                                    prepareValues(dimensionIndex.start_date),
                                    prepareValues(dimensionIndex.due_date),
                                ]);
                            }
                            if (params.seriesId === 'phasesData') {
                                return getWrapper([
                                    prepareValues(phaseDimensionIndex.name),
                                    prepareValues(phaseDimensionIndex.start_date),
                                    prepareValues(phaseDimensionIndex.due_date),
                                ]);
                            }
                            return `${params.dataIndex}`;
                        },
                    },
                    series: [
                        {
                            id: 'tasksData',
                            type: 'custom',
                            renderItem: this.renderGanttItem,
                            dimensions: i18nDimensions,
                            encode: {
                                x: [dimensionIndex.start_date, dimensionIndex.due_date],
                                y: dimensionIndex.index,
                            },
                            data: preparedRows,
                        },

                        {
                            id: 'tasksLabels',
                            type: 'custom',
                            renderItem: this.renderAxisLabelItem,
                            dimensions: i18nDimensions,
                            encode: {
                                x: -1,
                                y: 0,
                            },
                            data: preparedRows,
                        },
                        {
                            id: 'phasesData',
                            type: 'custom',
                            dimensions: i18nPhaseDimensions,
                            renderItem: this.renderPhaseItem,
                            encode: {
                                x: [2, 3],
                                y: 4,
                            },
                            data: Object.values(phasesMap).map(phase => {
                                const startTaskIdx = phase.tasks.byStartDate[phase.start_date].reduce(
                                    (minIndex, row) => Math.min(minIndex, row[dimensionIndex.index]),
                                    Infinity,
                                );
                                const dueTaskId = phase.tasks.byDueDate[phase.due_date].reduce(
                                    (maxIndex, row) => Math.max(maxIndex, row[dimensionIndex.index]),
                                    null,
                                );
                                return [
                                    phase.id,
                                    phase.name,
                                    phase.start_date,
                                    phase.due_date,
                                    startTaskIdx,
                                    dueTaskId,
                                ];
                            }),
                        },
                    ],
                };
                const firstTaskDate = preparedRows[0] ? preparedRows[0][dimensionIndex.start_date] : null;
                const lastTaskDate = preparedRows[preparedRows.length - 1]
                    ? preparedRows[preparedRows.length - 1][dimensionIndex.start_date]
                    : null;
                const today = moment();
                if (
                    firstTaskDate &&
                    lastTaskDate &&
                    !today.isBefore(moment(firstTaskDate)) &&
                    !today.isAfter(moment(lastTaskDate))
                ) {
                    option.series.push({
                        id: 'currentDayLine',
                        type: 'custom',
                        encode: {
                            x: 0,
                            y: -1,
                        },
                        data: [getStartDate(today)],
                        renderItem: (params, api) => {
                            const todayCoord = api.coord([api.value(0), 0])[0];
                            const chartHeight = api.getHeight() - grid.bottom - grid.top;
                            const gridTop = params.coordSys.y;
                            const gridBottom = gridTop + chartHeight;

                            return {
                                type: 'line',
                                ignore: todayCoord < grid.left || todayCoord > api.getWidth() - grid.right,
                                shape: {
                                    x1: todayCoord,
                                    y1: gridTop,
                                    x2: todayCoord,
                                    y2: gridBottom,
                                },
                                style: {
                                    stroke: 'rgba(255,0,0,0.3)',
                                    lineWidth: 2,
                                },
                                silent: true,
                            };
                        },
                    });
                }
                const oldZoom = this.$refs.gantt.chart.getOption()?.dataZoom;
                this.$refs.gantt.chart.setOption(option);
                if (oldZoom) {
                    this.$refs.gantt.chart.setOption({
                        dataZoom: oldZoom,
                    });
                }
            },
            renderGanttItem(params, api) {
                let categoryIndex = api.value(dimensionIndex.index);
                let startDate = api.coord([api.value(dimensionIndex.start_date), categoryIndex]);
                let endDate = api.coord([api.value(dimensionIndex.due_date), categoryIndex]);

                let barLength = endDate[0] - startDate[0];
                // Get the height corresponds to length 1 on y axis.
                let barHeight = api.size([0, 1])[1] * HEIGHT_RATIO;
                barHeight = ROW_HEIGHT;

                let x = startDate[0];
                let y = startDate[1] - barHeight;
                let barText = api.value(dimensionIndex.task_name);
                let barTextWidth = echartsFormat.getTextRect(barText).width;

                let rectNormal = this.clipRectByRect(params, {
                    x: x,
                    y: y,
                    width: barLength,
                    height: barHeight,
                });

                let estimate = +api.value(dimensionIndex.estimate);
                estimate = isNaN(estimate) ? 0 : estimate;
                let totalSpentTime = +api.value(dimensionIndex.total_spent_time);
                totalSpentTime = isNaN(totalSpentTime) ? 0 : totalSpentTime;
                let totalOffset = +api.value(dimensionIndex.total_offset);
                totalOffset = isNaN(totalOffset) ? 0 : totalOffset;
                const timeWithOffset = totalSpentTime + totalOffset;

                let taskProgressLine = 0;
                const multiplier = estimate > 0 ? timeWithOffset / estimate : 0;
                if (estimate != null && estimate >= 0) {
                    taskProgressLine = barLength * multiplier;
                }
                let rectProgress = this.clipRectByRect(params, {
                    x: x,
                    y: y + barHeight * 0.15,
                    width: taskProgressLine > barLength ? barLength : taskProgressLine, // fill bar length
                    height: barHeight * 0.7,
                });

                let taskId = api.value(dimensionIndex.id);
                const canvasWidth = api.getWidth() - grid.right;
                const canvasHeight = api.getHeight() - grid.bottom;

                let childrenLines = [];
                this.tasksRelationsMap[taskId]?.forEach((childRowData, index) => {
                    let childStartDate = api.coord([
                        childRowData[dimensionIndex.start_date],
                        childRowData[dimensionIndex.index],
                    ]);
                    let childY = childStartDate[1] - barHeight / 2;

                    // Start point at the end of the parent task
                    let startPoint = [endDate[0], endDate[1] - barHeight / 2];
                    if (startPoint[0] <= grid.left) {
                        startPoint[0] = grid.left;
                        startPoint[1] = childY; // if parent outside grid, don't draw line to the top
                    } else if (startPoint[0] >= canvasWidth) {
                        startPoint[0] = canvasWidth;
                    }
                    if (startPoint[1] <= grid.top) {
                        startPoint[1] = grid.top;
                    } else if (startPoint[1] >= canvasHeight) {
                        startPoint[1] = canvasHeight;
                    }

                    // Intermediate point, vertically aligned with the parent task end, but at the child task's y-level
                    let intermediatePoint = [endDate[0], childY];
                    if (intermediatePoint[0] <= grid.left) {
                        intermediatePoint[0] = grid.left;
                    } else if (intermediatePoint[0] >= canvasWidth) {
                        intermediatePoint[0] = canvasWidth;
                    }
                    if (intermediatePoint[1] <= grid.top) {
                        intermediatePoint[1] = grid.top;
                    } else if (intermediatePoint[1] >= canvasHeight) {
                        intermediatePoint[1] = canvasHeight;
                    }

                    // End point at the start of the child task
                    let endPoint = [childStartDate[0], childY];
                    if (endPoint[0] <= grid.left) {
                        endPoint[0] = grid.left;
                    } else if (endPoint[0] >= canvasWidth) {
                        endPoint[0] = canvasWidth;
                    }
                    if (endPoint[1] <= grid.top) {
                        endPoint[1] = grid.top;
                    } else if (endPoint[1] >= canvasHeight) {
                        endPoint[1] = canvasHeight;
                        endPoint[0] = endDate[0]; // if child outside grid, don't draw line to the right
                    }

                    const ignore =
                        endPoint[0] === grid.left ||
                        startPoint[0] === canvasWidth ||
                        endPoint[1] === grid.top ||
                        startPoint[1] === canvasHeight;

                    const childOrParentAreOutside =
                        startPoint[0] === grid.left ||
                        startPoint[1] === grid.top ||
                        endPoint[0] === canvasWidth ||
                        endPoint[1] === canvasHeight;

                    childrenLines.push({
                        type: 'polyline',
                        ignore: ignore,
                        silent: true,
                        shape: {
                            points: [startPoint, intermediatePoint, endPoint],
                        },
                        style: {
                            fill: 'transparent',
                            stroke: childOrParentAreOutside ? '#aaa' : '#333', // Line color
                            lineWidth: 1, // Line width
                            lineDash: childOrParentAreOutside ? [20, 3, 3, 3, 3, 3, 3, 3] : 'solid',
                        },
                    });
                });

                const rectTextShape = {
                    x: x + barLength + 5,
                    y: y + barHeight / 2,
                    width: barLength,
                    height: barHeight,
                };

                const textStyle = {
                    textFill: '#333',
                    width: 150,
                    height: barHeight,
                    text: barText,
                    textAlign: 'left',
                    textVerticalAlign: 'top',
                    lineHeight: 1,
                    fontSize: 12,
                    overflow: 'truncate',
                    elipsis: '...',
                };

                const progressPercentage = Number(multiplier).toLocaleString(this.$i18n.locale, {
                    style: 'percent',
                    minimumFractionDigits: 2,
                });

                const progressText =
                    multiplier === 0
                        ? ''
                        : echartsFormat.truncateText(
                              `${progressPercentage}`,
                              rectNormal?.width ?? 0,
                              api.font({ fontSize: 12 }),
                          );
                return {
                    type: 'group',
                    children: [
                        {
                            type: 'rect',
                            ignore: !rectNormal,
                            shape: rectNormal,
                            style: api.style({
                                fill: 'rgba(56,134,208,1)',
                                rectBorderWidth: 10,
                                text: progressText,
                                fontSize: 12,
                            }),
                        },
                        {
                            type: 'rect',
                            ignore: !rectProgress,
                            shape: rectProgress,
                            style: {
                                fill: 'rgba(0,55,111,.6)',
                            },
                        },
                        {
                            type: 'text',
                            ignore:
                                rectTextShape.x <= grid.left ||
                                rectTextShape.x > canvasWidth ||
                                rectTextShape.y <= grid.top + ROW_HEIGHT / 4 ||
                                rectTextShape.y >= canvasHeight - ROW_HEIGHT / 4,
                            clipPath: {
                                type: 'rect',
                                shape: {
                                    x: 0,
                                    y: 0 - ROW_HEIGHT / 2,
                                    width: textStyle.width,
                                    height: ROW_HEIGHT,
                                },
                            },
                            style: textStyle,
                            position: [rectTextShape.x, rectTextShape.y],
                        },
                        ...childrenLines,
                    ],
                };
            },
            renderPhaseItem(params, api) {
                let start = api.coord([
                    api.value(phaseDimensionIndex.start_date),
                    api.value(phaseDimensionIndex.first_task_id),
                ]);
                let end = api.coord([
                    api.value(phaseDimensionIndex.due_date),
                    api.value(phaseDimensionIndex.last_task_id),
                ]);

                const phaseHeight = ROW_HEIGHT / 3;

                // Calculate the Y position for the phase, maybe above all tasks
                let topY = start[1] - ROW_HEIGHT - phaseHeight - 5; // Determine how far above tasks you want to draw phases
                if (topY <= grid.top) {
                    topY = grid.top;
                }
                // when phase approach its last task set y to task y
                if (end[1] - ROW_HEIGHT - phaseHeight - 5 <= topY) {
                    topY = end[1] - ROW_HEIGHT - phaseHeight - 5;
                }
                let bottomY = topY + ROW_HEIGHT + phaseHeight + 5; // Determine the bottom Y based on the tasks' Y positions
                if (bottomY >= api.getHeight() - grid.bottom) {
                    bottomY = api.getHeight() - grid.bottom;
                }

                // Phase rectangle
                let rectShape = this.clipRectByRect(params, {
                    x: start[0],
                    y: topY,
                    width: end[0] - start[0],
                    height: phaseHeight, // Define the height of the phase rectangle
                });
                if (rectShape) {
                    rectShape.r = [5, 5, 0, 0];
                }

                const phaseName = echartsFormat.truncateText(
                    api.value(phaseDimensionIndex.name),
                    rectShape?.width ?? 0,
                    api.font({ fontSize: 14 }),
                );

                let rect = {
                    type: 'rect',
                    shape: rectShape,
                    ignore: !rectShape,
                    style: api.style({
                        fill: 'rgba(255,149,0,0.5)',
                        text: phaseName,
                        textStroke: 'rgb(181,106,0)',
                    }),
                };

                const lineWidth = 1;
                let y1 = topY + phaseHeight;
                if (y1 <= grid.top) {
                    y1 = grid.top;
                }
                // start vertical line
                let startLine = {
                    type: 'line',
                    ignore:
                        bottomY <= grid.top ||
                        y1 >= api.getHeight() - grid.bottom ||
                        start[0] + lineWidth / 2 <= grid.left ||
                        start[0] >= api.getWidth() - grid.right,
                    shape: {
                        x1: start[0] + lineWidth / 2,
                        y1,
                        x2: start[0] + lineWidth / 2,
                        y2: bottomY,
                    },
                    style: api.style({
                        stroke: 'rgba(255,149,0,0.5)', // Example style
                        lineWidth,
                        lineDash: [3, 3, 4],
                    }),
                };

                // End vertical line
                let endLine = {
                    type: 'line',
                    ignore:
                        bottomY <= grid.top ||
                        y1 >= api.getHeight() - grid.bottom ||
                        end[0] - lineWidth / 2 >= api.getWidth() - grid.right ||
                        end[0] <= grid.left,
                    shape: {
                        x1: end[0] - lineWidth / 2,
                        y1,
                        x2: end[0] - lineWidth / 2,
                        y2: bottomY,
                    },
                    style: api.style({
                        stroke: 'rgba(255,149,0,0.5)', // Example style
                        lineWidth,
                        lineDash: [3, 3, 4],
                    }),
                };

                return {
                    type: 'group',
                    children: [rect, startLine, endLine],
                };
            },

            renderAxisLabelItem(params, api) {
                const y = api.coord([0, api.value(0)])[1];
                const isOutside = y <= 70 || y > api.getHeight();
                return {
                    type: 'group',
                    position: [10, y],
                    ignore: isOutside,
                    children: [
                        {
                            type: 'path',
                            shape: {
                                d: 'M 0 0 L 0 -20 C 20.3333 -20 40.6667 -20 52 -20 C 64 -20 65 -2 70 -2 L 70 0 Z',
                                x: 12,
                                y: -ROW_HEIGHT,
                                width: 78,
                                height: ROW_HEIGHT,
                                layout: 'cover',
                            },
                            style: {
                                fill: '#368c6c',
                            },
                        },
                        {
                            type: 'text',
                            style: {
                                x: 15,
                                y: -3,
                                width: 80,
                                text: api.value(dimensionIndex.task_name),
                                textVerticalAlign: 'bottom',
                                textAlign: 'left',
                                textFill: '#fff',
                                overflow: 'truncate',
                            },
                        },
                        {
                            type: 'group',
                            name: 'got_to_task_btn',
                            children: [
                                {
                                    type: 'rect',
                                    shape: {
                                        x: -10,
                                        y: -ROW_HEIGHT,
                                        width: ROW_HEIGHT,
                                        height: ROW_HEIGHT,
                                        layout: 'center',
                                    },
                                    style: {
                                        fill: '#5988E5',
                                    },
                                },
                                {
                                    type: 'path',
                                    shape: {
                                        d: 'M15.7285 3.88396C17.1629 2.44407 19.2609 2.41383 20.4224 3.57981C21.586 4.74798 21.5547 6.85922 20.1194 8.30009L17.6956 10.7333C17.4033 11.0268 17.4042 11.5017 17.6976 11.794C17.9911 12.0863 18.466 12.0854 18.7583 11.7919L21.1821 9.35869C23.0934 7.43998 23.3334 4.37665 21.4851 2.5212C19.6346 0.663551 16.5781 0.905664 14.6658 2.82536L9.81817 7.69182C7.90688 9.61053 7.66692 12.6739 9.51519 14.5293C9.80751 14.8228 10.2824 14.8237 10.5758 14.5314C10.8693 14.2391 10.8702 13.7642 10.5779 13.4707C9.41425 12.3026 9.44559 10.1913 10.8809 8.75042L15.7285 3.88396Z M14.4851 9.47074C14.1928 9.17728 13.7179 9.17636 13.4244 9.46868C13.131 9.76101 13.1301 10.2359 13.4224 10.5293C14.586 11.6975 14.5547 13.8087 13.1194 15.2496L8.27178 20.1161C6.83745 21.556 4.73937 21.5863 3.57791 20.4203C2.41424 19.2521 2.44559 17.1408 3.88089 15.6999L6.30473 13.2667C6.59706 12.9732 6.59614 12.4984 6.30268 12.206C6.00922 11.9137 5.53434 11.9146 5.24202 12.2081L2.81818 14.6413C0.906876 16.5601 0.666916 19.6234 2.51519 21.4789C4.36567 23.3365 7.42221 23.0944 9.33449 21.1747L14.1821 16.3082C16.0934 14.3895 16.3334 11.3262 14.4851 9.47074Z',
                                        x: -10 * 0.8,
                                        y: -ROW_HEIGHT * 0.9,
                                        width: ROW_HEIGHT * 0.8,
                                        height: ROW_HEIGHT * 0.8,
                                        layout: 'center',
                                    },
                                    style: {
                                        fill: '#ffffff',
                                    },
                                },
                            ],
                        },
                    ],
                };
            },
            clipRectByRect(params, rect) {
                return echartsGraphic.clipRectByRect(rect, {
                    x: params.coordSys.x,
                    y: params.coordSys.y,
                    width: params.coordSys.width,
                    height: params.coordSys.height,
                });
            },
            websocketLeaveChannel(userId) {
                this.$echo.leave(`gantt.${userId}`);
            },
            websocketEnterChannel(userId, handlers) {
                const channel = this.$echo.private(`gantt.${userId}`);
                for (const action in handlers) {
                    channel.listen(`.gantt.${action}`, handlers[action]);
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .gantt {
        height: calc(100vh - 75px * 2);
        width: 100%;
    }
</style>
