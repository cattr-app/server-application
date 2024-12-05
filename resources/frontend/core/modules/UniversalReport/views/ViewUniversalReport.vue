<template>
    <div class="universal-report__view">
        <div class="universal-report__view-inner">
            <div class="at-container crud__content crud__item-view">
                <div class="page-controls row flex-between">
                    <div>
                        <h1 class="control-item title">
                            <Skeleton :loading="isDataLoading" width="200px">{{ reportName }}</Skeleton>
                        </h1>
                        <span>{{ $t('universal-report.data_for', [calendar.start, calendar.end]) }}</span>
                    </div>
                    <div class="control-items">
                        <at-button size="large" class="control-item" @click="$router.go(-1)"
                            >{{ $t('control.back') }}
                        </at-button>
                    </div>
                </div>
                <List :data="data" />
            </div>
        </div>
    </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import UniversalReportService from '../service/universal-report.service';
    import List from './components/List';
    import { Skeleton } from 'vue-loading-skeleton';
    import moment from 'moment';

    const service = new UniversalReportService();

    export default {
        components: {
            List,
            Skeleton,
        },
        name: 'UniversalReportView',
        computed: {
            ...mapGetters('universalreport', ['calendar']),
        },
        data() {
            return {
                isDataLoading: false,
                reportName: '',
                data: {
                    reportData: {},
                    reportCharts: {},
                    reportName: '',
                },
            };
        },
        async mounted() {
            this.isDataLoading = true;
            const { data } = await service.generate(this.$route.params.id, {
                start_at:
                    sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.start') ??
                    moment().format('YYYY-MM-DD'),
                end_at:
                    sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.end') ??
                    moment().format('YYYY-MM-DD'),
            });

            if (Array.isArray(data.data.reportCharts)) {
                data.data.reportCharts = {};
            }

            this.reportName = data.data.reportName;
            delete data.data.reportName;
            this.data = data.data;
            this.isDataLoading = false;
        },
    };
</script>

<style scoped lang="scss">
    .universal-report__view-inner {
        width: 100%;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
        max-height: 100vh;
        overflow: auto;
    }
</style>
