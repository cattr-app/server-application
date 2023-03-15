<template>
    <div class="calendar" @click="togglePopup">
        <at-input class="input" :readonly="true" :value="inputValue">
            <template #prepend>
                <i class="icon icon-chevron-left previous" @click.stop.prevent="selectPrevious"></i>
            </template>

            <template #append>
                <i class="icon icon-chevron-right next" @click.stop.prevent="selectNext"></i>
            </template>
        </at-input>

        <span class="calendar-icon icon icon-calendar" />

        <transition name="slide-up">
            <div
                v-show="showPopup"
                :class="{
                    'datepicker-wrapper': true,
                    'datepicker-wrapper--range': datePickerRange,
                    'at-select__dropdown at-select__dropdown--bottom': true,
                }"
                @click.stop
            >
                <div>
                    <at-tabs ref="tabs" v-model="tab" @on-change="onTabChange">
                        <at-tab-pane v-if="day" :label="$t('control.day')" name="day"></at-tab-pane>
                        <at-tab-pane v-if="week" :label="$t('control.week')" name="week"></at-tab-pane>
                        <at-tab-pane v-if="month" :label="$t('control.month')" name="month"></at-tab-pane>
                        <at-tab-pane v-if="range" :label="$t('control.range')" name="range"></at-tab-pane>
                    </at-tabs>
                </div>

                <date-picker
                    :key="$i18n.locale"
                    class="datepicker"
                    :append-to-body="false"
                    :clearable="false"
                    :editable="false"
                    :inline="true"
                    :lang="datePickerLang"
                    :type="datePickerType"
                    :range="datePickerRange"
                    :value="datePickerValue"
                    @change="onDateChange"
                >
                    <template #footer>
                        <div class="datepicker__footer">
                            <button class="mx-btn mx-btn-text" size="small" @click="setToday">
                                {{ $t('control.today') }}
                            </button>
                        </div>
                    </template>
                </date-picker>
            </div>
        </transition>
    </div>
</template>

<script>
    import moment from 'moment';
    import { getDateToday, getEndDay, getStartDay } from '@/utils/time';

    export default {
        name: 'Calendar',
        props: {
            day: {
                type: Boolean,
                default: true,
            },
            week: {
                type: Boolean,
                default: true,
            },
            month: {
                type: Boolean,
                default: true,
            },
            range: {
                type: Boolean,
                default: true,
            },
            initialTab: {
                type: String,
                default: 'day',
            },
            sessionStorageKey: {
                type: String,
                default: 'amazingcat.session.storage',
            },
        },
        data() {
            const { query } = this.$route;
            const today = this.getDateToday();

            const data = {
                showPopup: false,
                lang: null,
                datePickerLang: {},
            };

            const sessionData = {
                type: sessionStorage.getItem(this.sessionStorageKey + '.type'),
                start: sessionStorage.getItem(this.sessionStorageKey + '.start'),
                end: sessionStorage.getItem(this.sessionStorageKey + '.end'),
            };

            if (typeof query['type'] === 'string' && this.validateTab(query['type'])) {
                data.tab = query['type'];
            } else if (typeof sessionData.type === 'string' && this.validateTab(sessionData.type)) {
                data.tab = sessionData.type;
            } else {
                data.tab = this.initialTab;
            }

            if (typeof query['start'] === 'string' && this.validateDate(query['start'])) {
                data.start = query['start'];
            } else if (typeof sessionData.start === 'string' && this.validateDate(sessionData.start)) {
                data.start = sessionData.start;
            } else {
                data.start = today;
            }

            if (typeof query['end'] === 'string' && this.validateDate(query['end'])) {
                data.end = query['end'];
            } else if (typeof sessionData.end === 'string' && this.validateDate(sessionData.end)) {
                data.end = sessionData.end;
            } else {
                data.end = today;
            }

            switch (data.tab) {
                case 'day':
                case 'date':
                    data.end = data.start;
                    break;

                case 'week': {
                    const date = moment(data.start, 'YYYY-MM-DD', true);
                    if (date.isValid()) {
                        data.start = date.startOf('isoWeek').format('YYYY-MM-DD');
                        data.end = date.endOf('isoWeek').format('YYYY-MM-DD');
                    }
                    break;
                }

                case 'month': {
                    const date = moment(data.start, 'YYYY-MM-DD', true);
                    if (date.isValid()) {
                        data.start = date.startOf('month').format('YYYY-MM-DD');
                        data.end = date.endOf('month').format('YYYY-MM-DD');
                    }
                    break;
                }
            }

            return data;
        },
        mounted() {
            window.addEventListener('click', this.hidePopup);
            this.saveData(this.tab, this.start, this.end);
            this.emitChangeEvent();
            this.$nextTick(async () => {
                try {
                    const locale = await import(`vue2-datepicker/locale/${this.$i18n.locale}`);

                    this.datePickerLang = {
                        ...locale,
                        formatLocale: {
                            ...locale.formatLocale,
                            firstDayOfWeek: 1,
                        },
                        monthFormat: 'MMMM',
                    };
                } catch {
                    this.datePickerLang = {
                        formatLocale: { firstDayOfWeek: 1 },
                        monthFormat: 'MMMM',
                    };
                }
            });
        },
        beforeDestroy() {
            window.removeEventListener('click', this.hidePopup);
        },
        computed: {
            inputValue() {
                switch (this.tab) {
                    case 'date':
                    default:
                        return moment(this.start, 'YYYY-MM-DD').locale(this.$i18n.locale).format('MMM DD, YYYY');

                    case 'week': {
                        const start = moment(this.start, 'YYYY-MM-DD').locale(this.$i18n.locale).startOf('isoWeek');
                        const end = moment(this.end, 'YYYY-MM-DD').locale(this.$i18n.locale).endOf('isoWeek');
                        if (start.month() === end.month()) {
                            return start.format('MMM DD-') + end.format('DD, YYYY');
                        }

                        return start.format('MMM DD — ') + end.format('MMM DD, YYYY');
                    }

                    case 'month':
                        return moment(this.start, 'YYYY-MM-DD')
                            .locale(this.$i18n.locale)
                            .startOf('month')
                            .format('MMM, YYYY');

                    case 'range': {
                        const start = moment(this.start, 'YYYY-MM-DD').locale(this.$i18n.locale);
                        const end = moment(this.end, 'YYYY-MM-DD').locale(this.$i18n.locale);

                        if (start.year() === end.year()) {
                            return start.format('MMM DD, — ') + end.format('MMM DD, YYYY');
                        } else {
                            return start.format('MMM DD, YYYY — ') + end.format('MMM DD, YYYY');
                        }
                    }
                }
            },
            datePickerType() {
                switch (this.tab) {
                    case 'day':
                    case 'range':
                    default:
                        return 'date';

                    case 'week':
                        return 'week';

                    case 'month':
                        return 'month';
                }
            },
            datePickerRange() {
                return this.tab === 'range';
            },
            datePickerValue() {
                if (this.tab === 'range') {
                    return [moment(this.start, 'YYYY-MM-DD').toDate(), moment(this.end, 'YYYY-MM-DD').toDate()];
                }

                return moment(this.start, 'YYYY-MM-DD').toDate();
            },
        },
        methods: {
            getDateToday,
            validateTab(tab) {
                return ['day', 'date', 'week', 'month', 'range'].indexOf(tab) !== -1;
            },
            validateDate(date) {
                return moment(date, 'YYYY-MM-DD', true).isValid();
            },
            togglePopup() {
                this.showPopup = !this.showPopup;
            },
            hidePopup() {
                if (this.$el.contains(event.target)) {
                    return;
                }

                this.showPopup = false;
            },
            selectPrevious() {
                let start, end;
                switch (this.tab) {
                    case 'day':
                    default: {
                        const date = moment(this.start).subtract(1, 'day').format('YYYY-MM-DD');
                        start = date;
                        end = date;
                        break;
                    }

                    case 'week': {
                        const date = moment(this.start).subtract(1, 'week');
                        start = date.startOf('isoWeek').format('YYYY-MM-DD');
                        end = date.endOf('isoWeek').format('YYYY-MM-DD');
                        break;
                    }

                    case 'month': {
                        const date = moment(this.start).subtract(1, 'month');
                        start = date.startOf('month').format('YYYY-MM-DD');
                        end = date.endOf('month').format('YYYY-MM-DD');
                        break;
                    }

                    case 'range': {
                        const diff = moment(this.end).diff(this.start, 'days') + 1;
                        start = moment(this.start).subtract(diff, 'days').format('YYYY-MM-DD');
                        end = moment(this.end).subtract(diff, 'days').format('YYYY-MM-DD');
                        break;
                    }
                }

                this.saveData(this.tab, start, end);
                this.emitChangeEvent();
            },
            selectNext() {
                let start, end;
                switch (this.tab) {
                    case 'day':
                    default: {
                        const date = moment(this.start).add(1, 'day').format('YYYY-MM-DD');
                        start = date;
                        end = date;
                        break;
                    }

                    case 'week': {
                        const date = moment(this.start).add(1, 'week');
                        start = date.startOf('isoWeek').format('YYYY-MM-DD');
                        end = date.endOf('isoWeek').format('YYYY-MM-DD');
                        break;
                    }

                    case 'month': {
                        const date = moment(this.start).add(1, 'month');
                        start = date.startOf('month').format('YYYY-MM-DD');
                        end = date.endOf('month').format('YYYY-MM-DD');
                        break;
                    }

                    case 'range': {
                        const diff = moment(this.end).diff(this.start, 'days') + 1;
                        start = moment(this.start).add(diff, 'days').format('YYYY-MM-DD');
                        end = moment(this.end).add(diff, 'days').format('YYYY-MM-DD');
                        break;
                    }
                }

                this.saveData(this.tab, start, end);
                this.emitChangeEvent();
            },
            onTabChange({ index, name }) {
                this.tab = 'range';
                this.$nextTick(() => {
                    this.tab = name;
                });
            },
            setDate(value) {
                let start, end;

                switch (this.tab) {
                    case 'day':
                    default: {
                        const date = moment(value).format('YYYY-MM-DD');
                        start = date;
                        end = date;
                        break;
                    }

                    case 'week':
                        start = moment(value).startOf('isoWeek').format('YYYY-MM-DD');
                        end = moment(value).endOf('isoWeek').format('YYYY-MM-DD');
                        break;

                    case 'month':
                        start = moment(value).startOf('month').format('YYYY-MM-DD');
                        end = moment(value).endOf('month').format('YYYY-MM-DD');
                        break;

                    case 'range':
                        start = moment(value[0]).format('YYYY-MM-DD');
                        end = moment(value[1]).format('YYYY-MM-DD');
                        break;
                }

                this.saveData(this.tab, start, end);
                this.emitChangeEvent();
            },
            saveData(type, start, end) {
                this.tab = type;
                this.start = start;
                this.end = end;

                sessionStorage.setItem(this.sessionStorageKey + '.type', type);
                sessionStorage.setItem(this.sessionStorageKey + '.start', start);
                sessionStorage.setItem(this.sessionStorageKey + '.end', end);

                const { query } = this.$route;

                const searchParams = new URLSearchParams({ type, start, end }).toString();

                // HACK: The native history is used because changing
                // params via Vue Router closes all pending requests
                history.pushState(null, null, `?${searchParams}`);
            },
            emitChangeEvent() {
                this.$emit('change', {
                    type: sessionStorage.getItem(this.sessionStorageKey + '.type'),
                    start: sessionStorage.getItem(this.sessionStorageKey + '.start'),
                    end: sessionStorage.getItem(this.sessionStorageKey + '.end'),
                });
            },
            onDateChange(value) {
                this.showPopup = false;

                this.setDate(value);
            },
            setToday() {
                this.tab = 'day';
                this.$refs.tabs.setNavByIndex(0);
                this.setDate(new Date());
                this.hidePopup();
            },
        },
        watch: {
            $route(to, from) {
                const { query } = to;

                if (typeof query['type'] === 'string' && this.validateTab(query['type'])) {
                    sessionStorage.setItem(this.sessionStorageKey + '.type', (this.tab = query['type']));
                }

                if (typeof query['start'] === 'string' && this.validateDate(query['start'])) {
                    sessionStorage.setItem(this.sessionStorageKey + '.start', (this.start = query['start']));
                }

                if (typeof query['end'] === 'string' && this.validateDate(query['end'])) {
                    sessionStorage.setItem(this.sessionStorageKey + '.end', (this.end = query['end']));
                }

                this.emitChangeEvent();
            },
        },
    };
</script>

<style lang="scss" scoped>
    .calendar {
        position: relative;
    }

    .calendar-icon {
        position: absolute;
        top: 0;
        right: 2em;
        color: #2e2ef9;
        line-height: 40px;
        pointer-events: none;
    }

    .input {
        background: #ffffff;
        width: 330px;
        height: 40px;
        border: 1px solid #eeeef5;
        border-radius: 5px;

        cursor: pointer;

        &::v-deep {
            .at-input-group__prepend,
            .at-input-group__append,
            .at-input__original {
                border: 0;
                background: transparent;
            }

            .at-input-group__prepend,
            .at-input-group__append {
                padding: 0;
                font-weight: bold;
            }

            .at-input__original {
                cursor: pointer;
            }
        }

        .fa-calendar {
            color: #2e2ef9;
        }

        .previous,
        .next {
            color: #2e2ef9;

            display: flex;
            flex-flow: row nowrap;
            align-items: center;
            justify-content: center;

            width: 28px;
            height: 100%;

            cursor: pointer;
            user-select: none;
        }
    }

    .datepicker-wrapper {
        position: absolute;
        width: 320px;
        max-height: unset;

        &--range {
            width: 640px;
        }
    }

    .datepicker__footer {
        text-align: left;
    }

    .calendar::v-deep {
        .at-tabs__header {
            margin-bottom: 0;
        }

        .at-tabs-nav {
            display: flex;
            flex-flow: row nowrap;
            justify-content: space-between;
        }

        .at-tabs-nav__item {
            color: #c4c4cf;
            font-size: 15px;
            font-weight: 600;
            margin-right: 0;
            padding: 0;

            flex: 1;
            text-align: center;

            &--active {
                color: #2e2ef9;
            }

            &::after {
                background-color: #2e2ef9;
            }
        }

        .mx-datepicker {
            max-height: unset;
        }

        .mx-datepicker-main,
        .mx-datepicker-inline {
            border: none;
        }

        .mx-datepicker-header {
            padding: 0;
            border-bottom: none;
        }

        .mx-calendar {
            width: unset;
        }

        .mx-calendar-content {
            width: unset;
        }

        .mx-calendar-header {
            & > .mx-btn-text {
                padding: 0;
                width: 34px;
                text-align: center;
            }
        }

        .mx-calendar-header-label .mx-btn {
            color: #1a051d;
        }

        .mx-table thead {
            color: #b1b1be;
            font-weight: 600;
            text-transform: uppercase;
        }

        .mx-week-number-header,
        .mx-week-number {
            display: none;
        }

        .mx-table-date td {
            font-size: 13px;
        }

        .mx-table-date .cell:last-child {
            color: #ff5569;
        }

        .mx-table {
            .cell.not-current-month {
                color: #e7ecf2;
            }

            .cell.active {
                background: transparent;

                & > div {
                    display: inline-block;
                    background: #2e2ef9;
                    color: #ffffff;
                    border-radius: 7px;
                    width: 25px;
                    height: 25px;
                    line-height: 25px;
                }
            }

            .cell.in-range {
                background: transparent;

                & > div {
                    display: inline-block;
                    background: #eeeef5;
                    color: inherit;
                    border-top-left-radius: 5px;
                    border-bottom-left-radius: 5px;
                    width: 100%;
                    height: 22px;
                    line-height: 22px;
                }

                &:last-child > div {
                    border-top-right-radius: 5px;
                    border-bottom-right-radius: 5px;
                }
            }

            .cell.in-range + .cell.in-range > div {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }

            .mx-active-week {
                background: transparent;

                .cell > div {
                    border-radius: 0;
                }

                .cell:nth-child(3) > div {
                    border-top-left-radius: 5px;
                    border-bottom-left-radius: 5px;
                }

                .cell:nth-child(7) > div {
                    border-top-right-radius: 5px;
                    border-bottom-right-radius: 5px;
                }

                .cell + .cell:not(:last-child) > div {
                    display: inline-block;
                    background: #eeeef5;
                    color: #151941;
                    width: 100%;
                    height: 22px;
                    line-height: 22px;
                }

                .mx-week-number + .cell > div,
                .cell:last-child > div {
                    display: inline-block;
                    background: #2e2ef9;
                    color: #ffffff;
                    border-radius: 7px;
                    width: 25px;
                    height: 25px;
                    line-height: 25px;
                }
            }
        }

        .mx-table-month {
            color: #000000;

            .cell {
                height: 50px;
            }

            .cell.active > div {
                border-radius: 5px;
                width: 54px;
                height: 30px;
            }
        }

        .mx-table-year {
            color: #000000;

            .cell.active > div {
                width: 54px;
            }
        }

        .mx-btn:hover {
            color: #2e2ef9;
        }

        .mx-table .cell.today {
            color: #2a90e9;
        }
    }
</style>
