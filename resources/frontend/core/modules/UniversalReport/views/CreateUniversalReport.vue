<template>
    <div class="universal-report__form">
        <div class="data-entry controls-row">
            <Calendar
                class="controls-row__item"
                sessionStorageKey="amazingcat.session.storage.universalreport"
                @change="onCalendarChange"
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

        <at-select class="data-entry" @on-change="changeBase">
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
        <label>{{ $t(`field.data-objects.user.2`) }}</label>
        <fields-select
            class="data-entry"
            localePath="field.fields"
            :options="fields"
            :selectedOptions="selectedFields"
            @on-change="onFieldsChange"
        />
        <label>{{ $t(`field.data-objects.user.3`) }}</label>
        <v-select
            class="data-entry"
            :localePath="`field.fields.${selectedBase}.charts`"
            :options="charts"
            :selectedOptions="selectedCharts"
            @on-change="onChartsChange"
        />
        <div class="controls-row">
            <at-button class="controls-row__item" type="primary" @click="create">{{
                $t('universal-report.create-personal')
            }}</at-button>
            <at-button class="controls-row__item" type="primary" @click="createForCompany">
                {{ $t('universal-report.create-company') }}
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
                base: '',
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
            ]),
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
                clearStore: 'universalreport/clearStore',
            }),
            async changeBase(base) {
                this.base = base;
                this.setBase(base);

                let { data } = await service.getDataObjectsAndFields(base);
                this.setFields(data.data.fields);
                this.setDataObjects(data.data.dataObjects);
                this.setCharts(data.data.charts);

                let result = {};
                Object.keys(data.data.fields).forEach(item => (result[item] = []));

                this.setSelectedCharts([]);
                this.setSelectedDataObjects([]);
                this.setSelectedFields(result);
            },
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
            async create() {
                if (!this.name || !this.selectedBase || this.selectedDataObjects.length === 0) {
                    this.$Notify({
                        type: 'warning',
                        title: this.$t('universal-report.warning.title'),
                        message: this.$t('universal-report.warning.empty'),
                    });
                    return;
                }
                try {
                    const { data } = await service.create({
                        name: this.name,
                        base: this.selectedBase,
                        fields: this.selectedFields,
                        dataObjects: this.selectedDataObjects,
                        charts: this.selectedCharts,
                        type: 'personal',
                    });
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.save.success.title'),
                        message: this.$t('notification.save.success.message'),
                    });
                    this.$router.push({ name: 'report.universal.edit', params: { id: data.data.id } });
                } catch (error) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.save.error.title'),
                        message: this.$t('notification.save.error.message'),
                    });
                }
            },
            async createForCompany() {
                if (!this.name || !this.base || this.selectedDataObjects.length === 0) {
                    this.$Notify({
                        type: 'warning',
                        title: this.$t('universal-report.warning.title'),
                        message: this.$t('universal-report.warning.empty'),
                    });
                    return;
                }
                try {
                    const { data } = await service.create({
                        name: this.name,
                        base: this.selectedBase,
                        fields: this.selectedFields,
                        dataObjects: this.selectedDataObjects,
                        charts: this.selectedCharts,
                        type: 'company',
                    });
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.save.success.title'),
                        message: this.$t('notification.save.success.message'),
                    });
                    this.$router.push({ name: 'report.universal.edit', params: { id: data.data.id } });
                } catch (error) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.save.error.title'),
                        message: this.$t('notification.save.error.message'),
                    });
                }
            },
        },
        beforeDestroy() {
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
