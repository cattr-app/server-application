<template>
    <div class="data-entries">
        <h3>{{ $t('universal-report.task_information') }}</h3>
        <div v-if="report.name" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t('field.name') }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ report.task_name }}</span>
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
            <at-table :columns="columns" :data="Object.values(report.users)" />
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
        <ProjectInfo v-if="report.project" :project="report.project" />
    </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import { formatDurationString } from '@/utils/time';
    import { Skeleton } from 'vue-loading-skeleton';
    import ProjectInfo from './ProjectInfo';
    import Charts from '../../../Charts';

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
            ProjectInfo,
            Skeleton,
            Charts,
        },
        computed: {
            ...mapGetters('universalreport', ['selectedFields']),
            columns() {
                const columns = [];
                if (this.selectedFields.users?.includes('full_name')) {
                    columns.push({ title: this.$t('field.full_name'), key: 'full_name' });
                }

                if (this.selectedFields.users?.includes('email')) {
                    columns.push({ title: this.$t('field.email'), key: 'email' });
                }

                columns.push({
                    title: this.$t('field.total_spent'),
                    key: 'total_spent_time_by_user',
                    render: function (h, { column, item }) {
                        return h('span', formatDurationString(item[column.key]));
                    },
                });

                return columns;
            },
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
