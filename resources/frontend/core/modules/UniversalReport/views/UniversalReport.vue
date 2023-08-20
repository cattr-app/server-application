<template>
    <div class="universal-report container">
        <h1 class="page-title">{{ $t('navigation.universal-report') }}</h1>
        <div class="at-container">
            <div class="universal-report__side-bars">
                <div class="sidebar">
                    <p class="sidebar__header">{{ $t('universal-report.personal-reports') }}</p>
                    <at-menu class="data-entry" mode="vertical" router>
                        <at-menu-item
                            v-for="report in reports.personal"
                            :key="report.id"
                            :to="{ name: 'report.universal.edit', params: { id: report.id } }"
                        >
                            {{ report.name }}
                        </at-menu-item>
                    </at-menu>
                    <at-button class="button" type="primary"
                        ><router-link class="button__link" :to="{ name: 'report.universal.create' }">
                            {{ $t('universal-report.create_new_report') }}
                        </router-link>
                    </at-button>
                </div>
                <div class="sidebar">
                    <p class="sidebar__header">{{ $t('universal-report.company-reports') }}</p>
                    <at-menu class="data-entry" mode="vertical" router>
                        <at-menu-item
                            v-for="report in reports.company"
                            :key="report.id"
                            :to="{ name: 'report.universal.edit', params: { id: report.id } }"
                        >
                            {{ report.name }}
                        </at-menu-item>
                        <at-button class="button" type="primary"
                            ><router-link class="button__link" :to="{ name: 'report.universal.create' }">
                                {{ $t('universal-report.create_new_report') }}
                            </router-link>
                        </at-button>
                    </at-menu>
                </div>
            </div>
            <router-view />
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapMutations } from 'vuex';
    import UniversalReportService from '../service/universal-report.service';

    // import { formatDurationString } from '@/utils/time';
    // import Preloader from '@/components/Preloader';
    // import ExportDropdown from '@/components/ExportDropdown';
    // import { mapGetters } from 'vuex';
    // import debounce from 'lodash.debounce';

    const service = new UniversalReportService();

    export default {
        name: 'UniversalReport',
        data() {
            return {
                isDataLoading: false,
            };
        },
        computed: {
            ...mapGetters('universalreport', ['reports']),
        },
        methods: {
            ...mapMutations({
                setReports: 'universalreport/setReports',
                clearStore: 'universalreport/clearStore',
            }),
            selectReport(id) {
                // console.log(id);
            },
        },
        mounted() {
            this.clearStore();
            service.getReports().then(({ data }) => {
                // console.log(data.data);
                this.setReports(data.data);
            });
        },
    };
</script>

<style lang="scss" scoped>
    .data-entry {
        margin-bottom: $layout-02;
    }
    .universal-report {
        .at-container {
            display: flex;
        }
        &__side-bars {
            min-width: 240px;
            padding: 16px;
            border-right: 1px solid #e2ecf4;
            display: flex;
            flex-direction: column;

            .sidebar {
                height: 100%;
                &__header {
                    text-align: center;
                }
            }
        }

        .button {
            &__link {
                color: #e2ecf4;
            }
        }
        // &::v-deep {
        //     .at-menu__item--active > .at-menu__item-link {
        //         color: #6190e8 !important;
        //     }
        //     .at-menu__item-link::after {
        //         transform: scaleX(1) !important;
        //     }
        // }
    }
</style>
