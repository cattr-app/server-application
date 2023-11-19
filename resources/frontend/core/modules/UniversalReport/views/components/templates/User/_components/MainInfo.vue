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
        <Charts v-if="charts" :period="period" :charts="charts" :reportId="report.id" />
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import { Skeleton } from 'vue-loading-skeleton';
    import Charts from '../../../Charts';

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
            Charts,
            Skeleton,
        },
        methods: {
            formatDurationString,
        },
    };
</script>

<style lang="scss" scoped>
    .data-entry {
        margin: 16px 0;
    }
</style>
