<template>
    <div class="universal-report__view">
        <div class="col-24 col-lg-20">
            <div class="at-container crud__content crud__item-view">
                <div class="page-controls row flex-between">
                    <div>
                        <h1 class="control-item title">
                            <Skeleton :loading="isDataLoading" width="200px">{{ reportName }}</Skeleton>
                        </h1>
                        <span>{{ `Данные за ${calendar.start} - ${calendar.end}` }}</span>
                    </div>
                    <div class="control-items">
                        <at-button size="large" class="control-item" @click="$router.go($route.meta.navigation.from)"
                            >{{ $t('control.back') }}
                        </at-button>
                    </div>
                </div>
                <List :reportsList="reportData" />
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
                reportData: {},
            };
        },
        mounted() {
            this.isDataLoading = true;
            service
                .generate(this.$route.params.id, {
                    start_at:
                        sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.start') ??
                        moment().format('YYYY-MM-DD'),
                    end_at:
                        sessionStorage?.getItem('amazingcat.session.storage.universalreport' + '.end') ??
                        moment().format('YYYY-MM-DD'),
                })
                .then(({ data }) => {
                    this.reportName = data.data.reportName;
                    delete data.data.reportName;
                    this.reportData = data.data.reportData;
                    this.isDataLoading = false;
                });
        },
    };
</script>

<style scoped lang="scss">
    .universal-report__view {
        width: 100%;
    }
</style>
