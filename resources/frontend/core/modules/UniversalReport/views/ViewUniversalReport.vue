<template>
    <div class="universal-report__view">
        <div class="col-24 col-lg-20">
            <div class="at-container">
                <div class="page-controls">
                    <h1 class="control-item title">
                        <Skeleton :loading="isDataLoading" width="200px">{{ title }}</Skeleton>
                    </h1>
                    <div class="control-items">
                        <at-button size="large" class="control-item" @click="$router.go($route.meta.navigation.from)"
                            >{{ $t('control.back') }}
                        </at-button>
                    </div>
                </div>
                <div class="data-entries">
                    <div class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t(field.label) }}:</div>
                            <div class="col">
                                <Skeleton :loading="isDataLoading">
                                    <span></span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t(field.label) }}:</div>
                            <div class="col">
                                <Skeleton :loading="isDataLoading">
                                    <span></span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t(field.label) }}:</div>
                            <div class="col">
                                <Skeleton :loading="isDataLoading">
                                    <span></span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t(field.label) }}:</div>
                            <div class="col">
                                <Skeleton :loading="isDataLoading">
                                    <span></span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t(field.label) }}:</div>
                            <div class="col">
                                <Skeleton :loading="isDataLoading">
                                    <span></span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t(field.label) }}:</div>
                            <div class="col">
                                <Skeleton :loading="isDataLoading">
                                    <span></span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t(field.label) }}:</div>
                            <div class="col">
                                <Skeleton :loading="isDataLoading">
                                    <span></span>
                                </Skeleton>
                            </div>
                        </div>
                    </div> -->
                </div>
                <div class="controls-row">
                    <at-button class="controls-row__item" type="primary" @click="create">Скачать</at-button>
                    <at-button class="controls-row__item" type="primary" @click="createForCompany">
                        Создать для компании
                    </at-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import UniversalReportService from '../service/universal-report.service';

    const service = new UniversalReportService();

    export default {
        name: 'UniversalReportView',
        computed: {
            ...mapGetters('universalreport', ['calendar']),
        },
        mounted() {
            service.generate(this.$route.params.id, {
                start_at: this.calendar.start,
                end_at: this.calendar.end,
            });
        },
    };
</script>
