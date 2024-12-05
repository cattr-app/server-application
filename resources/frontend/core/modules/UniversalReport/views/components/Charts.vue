<template>
    <div class="diagrams">
        <div v-for="(chart, key) in charts" :key="key" class="data-entry">
            <h4>{{ $t(`field.fields.${selectedBase}.charts.${key}`) }}</h4>
            <div v-if="checkDataset(chart)" class="diagram__missing">
                <h5 class="diagram__missing__title">{{ $t(`universal-report.no-data-available`) }}</h5>
                <h6 class="diagram__missing__subtitle">
                    {{ $t(`universal-report.object-not-participate`) }}
                </h6>
            </div>
            <ChartLine
                v-else
                :id="key + reportId"
                :data="{
                    datasets: formatDataset(chart['datasets'][reportId]),
                    labels: period,
                }"
                :options="{
                    scales: {
                        y: {
                            ticks: {
                                callback: function (value, index, ticks) {
                                    return formatDurationString(value);
                                },
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }

                                    if (context.parsed.y !== null) {
                                        label += formatDurationString(context.parsed.y);
                                    }

                                    return label;
                                },
                            },
                        },
                    },
                }"
            />
        </div>
    </div>
</template>

<script>
    import cloneDeep from 'lodash/cloneDeep';
    import { mapGetters } from 'vuex';
    import { Line as ChartLine } from 'vue-chartjs';
    import { formatDurationString } from '@/utils/time';

    import {
        Chart as ChartJS,
        Title,
        Tooltip,
        Legend,
        BarElement,
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
    } from 'chart.js';

    ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement);

    export default {
        props: {
            period: {
                type: Array,
                required: true,
                default: () => [],
            },
            charts: {
                type: Object,
                required: true,
                default: () => {},
            },
            reportId: {
                type: [Number, String],
                required: true,
            },
        },
        components: {
            ChartLine,
        },
        computed: {
            ...mapGetters('universalreport', ['selectedBase']),
        },
        methods: {
            formatDurationString,
            checkDataset(chart) {
                const dataset = chart['datasets'][this.reportId];
                if (!dataset) {
                    return true;
                }

                chart = this.formatDataset(chart['datasets'][this.reportId]);
                return typeof chart[0].label !== 'string';
            },
            formatDataset(dataset) {
                if (typeof dataset === 'undefined') {
                    return [];
                }

                let result = cloneDeep(dataset);
                if (result?.data ?? false) {
                    result.data = Object.values(dataset.data);
                    return [result];
                }

                result = Object.values(result);
                result.forEach((element, index) => {
                    result[index].data = Object.values(element.data);
                });

                return result;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .diagram__missing {
        width: 100%;
        height: 200px;
        background: rgb(193, 193, 193, 0.7);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;

        &__subtitle {
            font-size: 12px;
        }
    }

    .diagrams {
        padding-top: 16px;
    }

    .data-entry {
        margin: 16px 0;
    }
</style>
