<template>
    <div class="gantt">
        <div class="controls-row flex-between">
            <div class="flex">
                <ProjectSelect class="controls-row__item" @change="onProjectsChange" />
            </div>
        </div>
        <v-chart ref="gantt" class="gantt__chart" :option="option" />
        <preloader v-if="isDataLoading" :is-transparent="true" class="gantt__loader" />
    </div>
</template>

<script>

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
    ];

    const dimensionIndex = Object.fromEntries(rawDimensions.map((el, i) => [el, i]));

    const dimensionsMap = new Map(rawDimensions.map((el, i) => [i, el]));

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
    import UserSelect from '@/components/UserSelect.vue';
    import TimezonePicker from '@/components/TimezonePicker.vue';
    import ProjectSelect from '@/components/ProjectSelect.vue';
    import Calendar from '@/components/Calendar.vue';
    import debounce from 'lodash/debounce';
    import Preloader from '@/components/Preloader.vue';
    import GanttService from '../services/gantt.service';
    import { getStartDate } from '@/utils/time';

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

    export default {
        name: 'Index',
        components: {
            Preloader,
            ProjectSelect,
            VChart,
        },
        provide: {
            // [THEME_KEY]: 'dark',
        },
        data() {
            return {
                isDataLoading: false,
                projectIDs: [],
                service: new GanttService(),
                option: {},
                totalRows: 0,
            };
        },
        async created() {
            await this.load();
        },
        mounted() {
            window.addEventListener('resize', this.onResize);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.onResize);
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
                    // TODO: maybe improve ux of sliders on resize?
                    // this.option.dataZoom = this.$refs.gantt.getOption().dataZoom;
                    // this.option.dataZoom[2].start = this.getYAxisZoomPercentage();
                    // this.option.dataZoom[2].end = 100;
                },
                100,
                {
                    maxWait: 200,
                },
            ),
            load: debounce(async function () {
                console.log('loadFired', this.projectIDs);
                this.isDataLoading = true;
                if (!this.projectIDs.length) {
                    this.isDataLoading = false;

                    return;
                }
                const ganttData = (await this.service.getGanttData(1)).data.data;
                this.totalRows = ganttData.tasks.length;
                const preparedRows = ganttData.tasks.map((item, index) => [index + 1].concat(...Object.values(item)));

                window.gantt = this.$refs.gantt.chart;

                // console.log('ChartHeight', totalRows);
                // console.log('ChartHeight', chartHeight);
                // console.log('canDraw', canDraw);
                // console.log('end', canDraw * 100);

                const option = {
                    tooltip: {},
                    animation: false,
                    toolbox: {
                        left: 20,
                        top: 0,
                        itemSize: 20,
                    },
                    title: {
                        text: `Gantt for ${ganttData.name}`,
                        left: 'center',
                    },
                    dataZoom: [
                        {
                            type: 'slider',
                            xAxisIndex: 0,
                            filterMode: 'weakFilter',
                            height: 20,
                            bottom: 0,
                            start: 0,
                            end: 50,
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
                            filterMode: 'weakFilter',
                            start: 0,
                            end: 50,
                            zoomOnMouseWheel: false,
                            moveOnMouseMove: true,
                        },
                        {
                            type: 'slider',
                            yAxisIndex: 0,
                            zoomLock: true,
                            width: 10,
                            right: 10,
                            top: 70,
                            bottom: 20,
                            start: 0, // TODO: calculate zoom to set proper size?
                            end: this.getYAxisZoomPercentage(this.totalRows), // TODO: calculate zoom to set proper size?
                            handleSize: 0,
                            showDetail: false,
                        },
                        {
                            type: 'inside',
                            id: 'insideY',
                            yAxisIndex: 0,
                            // startValue: 0,
                            // endValue: 10,
                            zoomOnMouseWheel: 'shift',
                            moveOnMouseMove: true,
                            moveOnMouseWheel: true,
                        },
                    ],
                    grid: {
                        show: true,
                        top: 70,
                        bottom: 20,
                        left: 100,
                        right: 20,
                        backgroundColor: '#fff',
                        borderWidth: 0,
                    },
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
                        // min: -1,
                        max: this.totalRows + 1, // _rawData.parkingApron.data.length https://echarts.apache.org/en/option.html#yAxis.max
                    },
                    series: [
                        {
                            id: 'tasksData',
                            type: 'custom',
                            renderItem: this.renderGanttItem,
                            dimensions: rawDimensions,
                            encode: {
                                x: [dimensionIndex.start_date, dimensionIndex.due_date],
                                y: dimensionIndex.index,
                                tooltip: [dimensionIndex.index, dimensionIndex.start_date, dimensionIndex.due_date],
                            },
                            data: preparedRows,
                        },
                        {
                            type: 'custom',
                            renderItem: this.renderAxisLabelItem,
                            dimensions: rawDimensions,
                            encode: {
                                x: -1,
                                y: 0,
                                tooltip: [dimensionIndex.task_name, dimensionIndex.start_date, dimensionIndex.due_date],
                            },
                            data: preparedRows,
                        },
                    ],
                };

                this.option = option;

                this.isDataLoading = false;
            }, 100),
            onProjectsChange(projectIDs) {
                this.projectIDs = [...projectIDs];

                this.load();
            },
            renderGanttItem(params, api) {
                // TODO: remove
                // window.$params = params;
                // window.$api = api;

                var categoryIndex = api.value(dimensionIndex.index);
                var startDate = api.coord([api.value(dimensionIndex.start_date), categoryIndex]);
                var endDate = api.coord([api.value(dimensionIndex.due_date), categoryIndex]);

                var barLength = endDate[0] - startDate[0];
                // Get the heigth corresponds to length 1 on y axis.
                var barHeight = api.size([0, 1])[1] * HEIGHT_RATIO;
                barHeight = ROW_HEIGHT;
                // console.log({
                //     barSize: api.size([0, 1]),
                //     barLength: barLength,
                //     startDate: startDate,
                //     endDate: endDate,
                //     params
                // });
                var x = startDate[0];
                var y = startDate[1] - barHeight;
                var barText = api.value(dimensionIndex.task_name) + '';
                var barTextWidth = echartsFormat.getTextRect(barText).width;
                var text = barLength > barTextWidth + 40 && x + barLength >= 180 ? barText : '';
                var rectNormal = this.clipRectByRect(params, {
                    x: x,
                    y: y,
                    width: barLength,
                    height: barHeight,
                });
                var rectVIP = this.clipRectByRect(params, {
                    x: x,
                    y: y + barHeight * 0.15,
                    width: barLength * 0.8, // fill bar length
                    height: barHeight * 0.7,
                });
                var rectText = this.clipRectByRect(params, {
                    x: x,
                    y: y,
                    width: barLength,
                    height: barHeight,
                });

                const linePoints = echartsGraphic.clipPointsByRect(
                    [
                        [40, -10],
                        [-30, -5],
                        [-76.5, 20],
                        [-63.5, 40],
                        [-22.1, 50],
                    ],
                    // {
                    //     x: x,
                    //     y: y,
                    //     width: barLength,
                    //     height: barHeight,
                    // }
                    rectNormal
                );
                linePoints.forEach((point, index) => {
                    point[0] = point[0] + 10 * index;
                    point[1] = point[1] + 10 * index;
                });
                // console.log('linePoints', linePoints);
                return {
                    type: 'group',
                    children: [
                        {
                            type: 'rect',
                            ignore: !rectNormal,
                            shape: rectNormal,
                            style: {
                                fill: 'rgba(56,134,208,1)',
                                rectBorderWidth: 10,
                            },
                        },
                        {
                            type: 'rect',
                            ignore: !rectVIP && !api.value(4),
                            shape: rectVIP,
                            style: { fill: 'rgba(0,55,111,.6)' },
                        },
                        {
                            type: 'rect',
                            ignore: !rectText,
                            shape: rectText,
                            style: {
                                fill: 'transparent',
                                stroke: 'transparent',
                                text: text,
                                textFill: '#fff',
                            },
                        },
                        {
                            type: 'line',
                            data: [[2, 2], [10, 10]],
                            symbolSize: 10,
                            x: rectNormal.x + rectNormal.width,
                            y: rectNormal.y + rectNormal.height / 2,
                            // shape: echartsGraphic.clipPointsByRect([10,20,30,40], rectNormal),
                            style: {
                                fill: 'red',
                                stroke: 'red',
                                text: '13213',
                                textFill: 'red',
                            },
                        },
                    ],
                };
            },
            renderAxisLabelItem(params, api) {
                // console.log(params);
                // console.log(api);
                window.$api = api;

                var y = api.coord([0, api.value(0)])[1];
                // var y = api.coord([0, params.dataIndex])[1];
                // if (y < params.coordSys.y + 5) {
                //     return;
                // }
                // [0, 'AB94', 'W', true],
                return {
                    type: 'group',
                    position: [10, y],
                    children: [
                        {
                            type: 'path',
                            shape: {
                                d: 'M0,0 L0,-20 L30,-20 C42,-20 38,-1 50,-1 L70,-1 L70,0 Z',
                                x: 0,
                                y: -ROW_HEIGHT,
                                width: 90,
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
                                x: 24,
                                y: -3,
                                text: api.value(dimensionIndex.task_name),
                                textVerticalAlign: 'bottom',
                                textAlign: 'center',
                                textFill: '#fff',
                            },
                        },
                        {
                            type: 'text',
                            style: {
                                x: 75,
                                y: -2,
                                textVerticalAlign: 'bottom',
                                textAlign: 'center',
                                text: api.value(dimensionIndex.task_name),
                                textFill: '#000',
                            },
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
        },
    };
</script>

<style lang="scss" scoped>
    .gantt {
        height: calc(100vh - 75px * 2);
        width: 100%;
        //position: absolute;
        //top: 0;
        //bottom: 0;
        //right: 0;
        //left: 0;
    }

    .dashboard {
        &__routes {
            margin-bottom: 1em;
            display: flex;
        }

        &__link {
            margin-right: $layout-03;
            font-size: 1.8rem;

            &:last-child {
                margin-right: initial;
            }

            a {
                color: #b1b1be;
            }

            .router-link-active {
                color: #2e2ef9;
            }
        }
    }
</style>
