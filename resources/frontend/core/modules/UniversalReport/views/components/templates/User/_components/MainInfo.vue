<template>
    <div class="data-entries">
        <h3>{{ $t('universal-report.user_information') }}</h3>
        <div v-if="report.full_name" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.full_name') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.full_name }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="report.email" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.email') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.email }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="report.worked_time_day" class="data-entry">
            <h4>{{ $t('universal-report.worked_by_day') }}</h4>
            <div v-for="(timeDay, day) in report.worked_time_day" :key="day" class="row">
                <div class="col-6 label">{{ day }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ formatDurationString(timeDay) }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="charts">
            <div v-for="(chart, key) in charts" :key="key" class="data-entry">
                <h4>{{ $t(`field.fields.${selectedMain}.charts.${key}`) }}</h4>
                <ChartLine
                    :id="key + report.id"
                    :data="{
                        datasets: formatDataset(chart['datasets'][report.id]),
                        labels: period,
                    }"
                />
            </div>
        </div>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import cloneDeep from 'lodash/cloneDeep';
    import { Line as ChartLine } from 'vue-chartjs';
    import { mapGetters } from 'vuex';
    import { Skeleton } from 'vue-loading-skeleton';

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
            report: {
                type: Object,
                required: true,
            },
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
        },
        components: {
            ChartLine,
            Skeleton,
        },
        computed: {
            ...mapGetters('universalreport', ['selectedMain']),
        },
        methods: {
            formatDurationString,
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
