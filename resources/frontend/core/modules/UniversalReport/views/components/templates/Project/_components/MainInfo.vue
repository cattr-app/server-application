<template>
    <div class="data-entries">
        <h3>{{ $t('universal-report.project_information') }}</h3>
        <div v-if="report.name" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.name') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.name }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="report.status" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.status') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.status }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="report.created_at" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.created_at') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.created_at }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="report.priority" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.priority') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.priority }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="report.description" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.description') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.description }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
        <div v-if="report.users" class="data-entry">
            <h3>{{ $t('field.members') }}</h3>
            <at-table
                :columns="[
                    { title: $t('field.full_name'), key: 'full_name' },
                    { title: $t('field.email'), key: 'email' },
                    { title: $t('field.total_spent'), key: 'total_spent_time_by_user' },
                ]"
                :data="Object.values(report.users)"
            />
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
        <div v-if="Object.keys(charts).length">
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
    import { Skeleton } from 'vue-loading-skeleton';
    import { Line as ChartLine } from 'vue-chartjs';
    import { mapGetters } from 'vuex';
    import cloneDeep from 'lodash/cloneDeep';

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
            charts: {
                type: Object,
                required: true,
                default: () => {},
            },
            period: {
                type: Array,
                required: true,
                default: () => [],
            },
        },
        components: {
            Skeleton,
            ChartLine,
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
        computed: {
            ...mapGetters('universalreport', ['selectedMain']),
        },
    };
</script>
