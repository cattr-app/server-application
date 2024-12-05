<template>
    <div class="universal-report__form">
        <div class="data-entry controls-row">
            <Calendar
                class="controls-row__item"
                sessionStorageKey="amazingcat.session.storage.universalreport"
                @change="onCalendarChange"
            />
            <div class="controls-row__item controls-row__item--left-auto">
                <small v-if="companyData.timezone">
                    {{ $t('universal-report.report_timezone', [companyData.timezone]) }}
                </small>
            </div>
            <ExportDropdown
                class="export-btn dropdown controls-row__btn controls-row__item"
                position="left-top"
                trigger="hover"
                @export="onExport"
            />
        </div>
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
        <at-select class="data-entry" :value="base" @on-change="changeBase">
            <at-option
                v-for="base in bases"
                :key="base"
                :value="base"
                :label="$t(`field.data-objects.${base}.1`)"
            ></at-option>
        </at-select>
        <div v-if="base">
            <label>{{ $t(`field.data-objects.${base}.1`) }}</label>
        </div>
        <obj-data-select
            class="data-entry"
            :options="dataObjects"
            :base="selectedBase"
            :selectedOptions="selectedDataObjects"
            @on-change="change"
        />
        <div v-if="base">
            <label>{{ $t(`field.data-objects.${base}.2`) }}</label>
        </div>
        <fields-select
            class="data-entry"
            localePath="field.fields"
            :options="fields"
            :selectedOptions="selectedFields"
            @on-change="onFieldsChange"
        />
        <div v-if="base">
            <label>{{ $t(`field.data-objects.${base}.3`) }}</label>
        </div>
        <v-select
            class="data-entry"
            :localePath="`field.fields.${selectedBase}.charts`"
            :options="charts"
            :selectedOptions="selectedCharts"
            @on-change="onChartsChange"
        />
        <div class="controls-row">
            <at-button class="controls-row__item" type="primary" @click="edit">{{
                $t('universal-report.save')
            }}</at-button>
            <at-button class="controls-row__item" type="success">
                <router-link
                    class="link"
                    :to="{ name: 'report.universal.view', params: { id: this.$route.params.id } }"
                >
                    {{ $t('universal-report.generate') }}
                </router-link>
            </at-button>
            <at-button class="controls-row__item" type="error" @click="remove">{{
                $t('universal-report.remove')
            }}</at-button>
        </div>
    </div>
</template>

<script>
    import Calendar from '@/components/Calendar';
    import ExportDropdown from '@/components/ExportDropdown';
    import { ValidationProvider } from 'vee-validate';
    import ObjDataSelect from './ObjectsDataSelect';
    import UniversalReportService from '../service/universal-report.service';
    import VSelect from './Select';
    import FieldsSelect from './FieldsSelect';
    import { mapGetters, mapMutations } from 'vuex';
    import { getStartDate, getStartOfDayInTimezone, getEndOfDayInTimezone } from '@/utils/time';

    const service = new UniversalReportService();

    export default {
        name: 'EditUniversalReport',
        components: {
            Calendar,
            ExportDropdown,
            ValidationProvider,
            ObjDataSelect,
            VSelect,
            FieldsSelect,
        },
        data() {
            return {
                isDataLoading: false,
                projects: [],
                reportDate: null,
                projectsList: [],
                projectReportsList: {},
                base: '',
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
                'bases',
                'fields',
                'selectedBase',
                'selectedFields',
                'dataObjects',
                'selectedDataObjects',
                'charts',
                'selectedCharts',
                'type',
            ]),
            ...mapGetters('user', ['companyData']),
            reportName: {
                get() {
                    return this.name;
                },
                set(newName) {
                    this.setName(newName);
                },
            },
        },
        async mounted() {
            await service.getBases().then(({ data }) => {
                this.setBases(data.data);
            });

            await service.show(this.$route.params.id).then(({ data }) => {
                service.getDataObjectsAndFields(data.data.base).then(({ data }) => {
                    this.setFields(data.data.fields);
                    this.setDataObjects(data.data.dataObjects);
                    this.setCharts(data.data.charts);
                });

                this.setName(data.data.name);
                this.setBase(data.data.base);
                this.base = data.data.base;
                this.setSelectedFields(data.data.fields);
                this.setSelectedDataObjects(data.data.data_objects);
                this.setSelectedCharts(data.data.charts);
                this.setType(data.data.type);
            });
        },
        methods: {
            ...mapMutations({
                setName: 'universalreport/setName',
                setBases: 'universalreport/setBases',
                setBase: 'universalreport/setBase',
                setFields: 'universalreport/setFields',
                setSelectedFields: 'universalreport/setSelectedFields',
                setDataObjects: 'universalreport/setDataObjects',
                setSelectedDataObjects: 'universalreport/setSelectedDataObject',
                setCalendarData: 'universalreport/setCalendarData',
                setCharts: 'universalreport/setCharts',
                setSelectedCharts: 'universalreport/setSelectedCharts',
                setType: 'universalreport/setType',
                clearStore: 'universalreport/clearStore',
            }),
            change(newOptions) {
                this.setSelectedDataObjects(newOptions);
            },
            changeBase(base) {
                if (this.selectedBase === base) {
                    return;
                }

                this.base = base;
                this.setBase(base);

                service.getDataObjectsAndFields(base).then(({ data }) => {
                    this.setFields(data.data.fields);
                    this.setDataObjects(data.data.dataObjects);
                    this.setCharts(data.data.charts);

                    let result = {};
                    Object.keys(data.data.fields).forEach(item => (result[item] = []));

                    this.setSelectedCharts([]);
                    this.setSelectedDataObjects([]);
                    this.setSelectedFields(result);
                });
            },
            onCalendarChange({ type, start, end }) {
                this.datepickerDateStart = getStartDate(start);
                this.datepickerDateEnd = getStartDate(end);
                this.setCalendarData({ type, start, end });
            },
            onUsersSelect(uids) {
                this.userIds = uids;
                this.fetchData();
            },
            onProjectsChange(projectIDs) {
                this.projectsList = projectIDs;
                this.fetchData();
            },
            async onExport(format) {
                try {
                    const { data } = await service.downloadReport(
                        getStartOfDayInTimezone(this.datepickerDateStart, this.companyData.timezone),
                        getEndOfDayInTimezone(this.datepickerDateEnd, this.companyData.timezone),
                        this.$route.params.id,
                        format,
                    );

                    window.open(data.data.url, '_blank');
                } catch ({ response }) {
                    if (process.env.NODE_ENV === 'development') {
                        console.log(response ? response : 'request to reports is canceled');
                    }
                }
            },
            onFieldsChange(fields) {
                console.log(fields);
                this.setSelectedFields(fields);
            },
            selectReport(id) {
                console.log(id);
            },
            onChartsChange(charts) {
                this.setSelectedCharts(charts);
            },
            async edit() {
                if (!this.name || !this.base || this.selectedDataObjects.length === 0) {
                    this.$Notify({
                        type: 'warning',
                        title: this.$t('universal-report.warning.title'),
                        message: this.$t('universal-report.warning.empty'),
                    });
                    return;
                }
                try {
                    await service.edit(this.$route.params.id, {
                        name: this.name,
                        base: this.selectedBase,
                        fields: this.selectedFields,
                        dataObjects: this.selectedDataObjects,
                        charts: this.selectedCharts,
                        type: this.type,
                    });
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.save.success.title'),
                        message: this.$t('notification.save.success.message'),
                    });
                } catch (error) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.save.error.title'),
                        message: this.$t('notification.save.error.message'),
                    });
                }
            },
            async remove() {
                try {
                    await service.deleteItem(this.$route.params.id);
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.record.delete.success.title'),
                        message: this.$t('notification.record.delete.success.message'),
                    });
                    this.$router.push({ name: 'report.universal' });
                } catch (error) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.record.delete.error.title'),
                        message: this.$t('notification.record.delete.error.message'),
                    });
                }
            },
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
