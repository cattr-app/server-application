<template>
    <div class="universal-report container">
        <h1 class="page-title">{{ $t('navigation.universal-report') }}</h1>
        <div class="at-container">
            <div class="universal-report__side-bars">
                <div class="sidebar">
                    <p class="sidebar__header">{{ $t('universal-report.personal-reports') }}</p>
                    <div v-if="reports && reports.personal">
                        <at-menu v-if="reports.personal.length > 0" class="data-entry" mode="vertical" router>
                            <at-menu-item
                                v-for="report in reports.personal"
                                :key="report.id"
                                :to="{ name: 'report.universal.edit', params: { id: report.id } }"
                            >
                                {{ report.name }}
                            </at-menu-item>
                        </at-menu>
                        <at-menu v-else>
                            <p class="sidebar__header data-entry text-no-report">
                                {{ $t('universal-report.no-personal-reports') }}
                            </p>
                        </at-menu>
                    </div>
                </div>
                <div class="sidebar">
                    <p class="sidebar__header">{{ $t('universal-report.company-reports') }}</p>
                    <at-menu v-if="reports.company.length > 0" class="data-entry" mode="vertical" router>
                        <at-menu-item
                            v-for="report in reports.company"
                            :key="report.id"
                            :to="{ name: 'report.universal.edit', params: { id: report.id } }"
                        >
                            {{ report.name }}
                        </at-menu-item>
                    </at-menu>
                    <at-menu v-else>
                        <p class="sidebar__header data-entry text-no-report">
                            {{ $t('universal-report.no-company-reports') }}
                        </p>
                    </at-menu>
                    <at-button class="button" type="primary" @click="redirectToCreate">
                        {{ $t('universal-report.create_new_report') }}
                    </at-button>
                </div>
            </div>
            <div v-if="showCreateMessage" class="universal-no-report text-no-report">
                {{ $t('universal-report.сreate-select-existing-report') }}
            </div>
            <router-view />
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapMutations } from 'vuex';
    import UniversalReportService from '../service/universal-report.service';

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
            showCreateMessage() {
                return this.$route.name === 'report.universal';
            },
        },
        methods: {
            redirectToCreate() {
                this.$router.push({ name: 'report.universal.create' });
            },
            ...mapMutations({
                setReports: 'universalreport/setReports',
                clearStore: 'universalreport/clearStore',
            }),
        },
        async mounted() {
            this.clearStore();
            const { data } = await service.getReports();

            this.setReports(data.data);
        },
    };
</script>

<style lang="scss" scoped>
    .data-entry {
        padding: 16px 0;
    }

    .text-no-report {
        font-size: 80%;
        color: grey;
    }

    .universal-no-report {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    .universal-report {
        .at-container {
            display: flex;
        }

        &__side-bars {
            border-right: 1px solid #e2ecf4;
            display: flex;
            flex-direction: column;
            padding: 16px 0;

            .sidebar {
                padding-bottom: 16px;
                flex-shrink: 0;

                &__header {
                    text-align: center;
                }
            }
        }

        .button {
            margin: 0 auto;
            display: block;

            &__link {
                color: #e2ecf4;
                margin: 10px;
            }
        }
    }
</style>
