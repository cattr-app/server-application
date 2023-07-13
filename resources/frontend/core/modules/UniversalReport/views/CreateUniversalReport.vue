<template>
    <div class="universal-report__form">
        <Calendar class="data-entry" @change="onCalendarChange" />
        <validation-provider v-slot="{ errors }" :rules="'required'" :name="$t('field.name')">
            <at-input
                v-model="reportName"
                class="data-entry"
                :placeholder="$t('field.name')"
                :type="'text'"
                :status="errors.length > 0 ? 'error' : ''"
            ></at-input>
            <small>{{ errors[0] }}</small>
        </validation-provider>
        <at-select v-model="main" class="data-entry">
            <at-option v-for="main in mains" :key="main" :value="main" :label="$t(`field.options.${main}`)"></at-option>
        </at-select>
        <obj-data-select
            class="data-entry"
            :options="dataObjects"
            :main="selectedMain"
            :selectedOptions="selectedDataObjects"
            @on-change="change"
        />
        <fields-select
            class="data-entry"
            localePath="field.fields"
            :options="fields"
            :selectedOptions="selectedFields"
            @on-change="onFieldsChange"
        />
        <v-select
            class="data-entry"
            localePath="field.charts"
            :options="charts"
            :selectedOptions="selectedCharts"
            @on-change="onChartsChange"
        />
        <div class="controls-row">
            <at-button class="controls-row__item" type="primary" @click="create">Создать</at-button>
            <at-button class="controls-row__item" type="primary" @click="createForCompany">
                Создать для компании
            </at-button>
        </div>
    </div>
</template>

<script>
    import Calendar from '@/components/Calendar';
    import { ValidationProvider } from 'vee-validate';
    import ObjDataSelect from './ObjectsDataSelect';
    import VSelect from './Select';
    import FieldsSelect from './FieldsSelect';
    import { mapGetters, mapMutations } from 'vuex';
    import UniversalReportService from '../service/universal-report.service';

    const service = new UniversalReportService();

    export default {
        name: 'CreateUniversalReport',
        components: {
            Calendar,
            ValidationProvider,
            VSelect,
            ObjDataSelect,
            FieldsSelect,
        },
        data() {
            return {
                isDataLoading: false,
                projects: [],
                reportDate: null,
                projectsList: [],
                projectReportsList: {},
                userIds: [],
                reports: {
                    personal: [
                        {
                            id: 1,
                            name: 'aaaa',
                            access: true,
                        },
                    ],
                    company: [
                        {
                            id: 1,
                            name: 'bbb',
                            access: true,
                        },
                    ],
                },
            };
        },
        computed: {
            ...mapGetters('universalreport', [
                'name',
                'service',
                'mains',
                'fields',
                'selectedMain',
                'selectedFields',
                'dataObjects',
                'selectedDataObjects',
                'charts',
                'selectedCharts',
            ]),
            main: {
                get() {
                    return this.selectedMain;
                },
                set(newMain) {
                    this.setMain(newMain);

                    service.getDataObjectsAndFields(newMain).then(({ data }) => {
                        this.setFields(data.data.fields);
                        this.setDataObjects(data.data.dataObjects);
                        this.setCharts(data.data.charts);

                        let result = {};
                        Object.keys(data.data.fields).forEach(item => (result[item] = []));

                        this.setSelectedFields(result);
                    });
                },
            },
            reportName: {
                get() {
                    return this.name;
                },
                set(newName) {
                    this.setName(newName);
                },
            },
        },
        methods: {
            ...mapMutations({
                setName: 'universalreport/setName',
                setMains: 'universalreport/setMains',
                setMain: 'universalreport/setMain',
                setFields: 'universalreport/setFields',
                setSelectedFields: 'universalreport/setSelectedFields',
                setDataObjects: 'universalreport/setDataObjects',
                setSelectedDataObjects: 'universalreport/setSelectedDataObject',
                setCalendarData: 'universalreport/setCalendarData',
                setCharts: 'universalreport/setCharts',
                setSelectedCharts: 'universalreport/setSelectedCharts',
                clearStore: 'universalreport/clearStore',
            }),
            change(newOptions) {
                this.setSelectedDataObjects(newOptions);
            },
            onCalendarChange({ type, start, end }) {
                this.setCalendarData({ type, start, end });
            },
            onFieldsChange(fields) {
                this.setSelectedFields(fields);
            },
            selectReport(id) {
                console.log(id);
            },
            onChartsChange(charts) {
                this.setSelectedCharts(charts);
            },
            create() {
                service
                    .create({
                        name: this.name,
                        main: this.selectedMain,
                        fields: this.selectedFields,
                        dataObjects: this.selectedDataObjects,
                        charts: this.selectedCharts,
                        type: 'personal',
                    })
                    .then(({ data }) => {
                        this.$Notify({
                            type: 'success',
                            title: this.$t('notification.save.success.title'),
                            message: this.$t('notification.save.success.message'),
                        });
                        this.$router.push({ name: 'report.universal.edit', params: { id: data.data.id } });
                    })
                    .catch(() => {
                        this.$Notify({
                            type: 'error',
                            title: this.$t('notification.save.error.title'),
                            message: this.$t('notification.save.error.message'),
                        });
                    });
            },
            createForCompany() {
                service
                    .create({
                        name: this.name,
                        main: this.selectedMain,
                        fields: this.selectedFields,
                        dataObjects: this.selectedDataObjects,
                        charts: this.selectedCharts,
                        type: 'company',
                    })
                    .then(({ data }) => {
                        this.$Notify({
                            type: 'success',
                            title: this.$t('notification.save.success.title'),
                            message: this.$t('notification.save.success.message'),
                        });

                        this.$router.push({ name: 'report.universal.edit', params: { id: data.data.id } });
                    })
                    .catch(() => {
                        this.$Notify({
                            type: 'error',
                            title: this.$t('notification.save.error.title'),
                            message: this.$t('notification.save.error.message'),
                        });
                    });
            },
        },
        beforeDestroy() {
            console.log(1111111);
            this.clearStore();
        },
    };
</script>

<style lang="scss" scoped>
    .universal-report {
        .at-container {
            display: flex;
        }
        &__side-bars {
            min-width: 240px;
            padding: 16px;
            // margin-right: $layout-03;
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
        &__form {
            width: 100%;
            padding: 1rem 1.5rem 2rem;
            .data-entry {
                margin-bottom: $layout-02;
            }
        }

        .link {
            color: #e2ecf4;
        }

        &::v-deep {
            .at-menu__item--active > .at-menu__item-link {
                color: #6190e8 !important;
            }
            .at-menu__item-link::after {
                transform: scaleX(1) !important;
            }
        }
    }
</style>
