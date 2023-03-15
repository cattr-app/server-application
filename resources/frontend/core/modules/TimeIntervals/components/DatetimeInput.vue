<template>
    <div ref="datetimeinput" class="datetimeinput" @click="togglePopup">
        <div class="at-input">
            <at-input class="input" :readonly="true" :value="inputValue" />

            <transition name="slide-up">
                <div
                    v-show="showPopup"
                    class="datepicker-wrapper at-select__dropdown at-select__dropdown--bottom"
                    @click.stop
                >
                    <div class="datepicker__main">
                        <date-picker
                            class="datepicker"
                            :append-to-body="false"
                            :clearable="false"
                            :editable="false"
                            :inline="true"
                            :lang="datePickerLang"
                            type="day"
                            :value="datePickerValue"
                            :disabled-date="disabledDate"
                            @change="onDateChange"
                        />

                        <ul class="hour-select">
                            <li
                                v-for="h in hours"
                                :key="h"
                                class="item"
                                :class="{ selected: hour === h }"
                                @click="setHour(h)"
                            >
                                {{ h.toString().padStart(2, '0') }}
                            </li>
                        </ul>

                        <ul class="minute-select">
                            <li
                                v-for="m in minutes"
                                :key="m"
                                class="item"
                                :class="{ selected: minute === m }"
                                @click="setMinute(m)"
                            >
                                {{ m.toString().padStart(2, '0') }}
                            </li>
                        </ul>
                    </div>

                    <div class="datepicker__footer">
                        <at-button size="small" @click="onDateChange(new Date())">{{ $t('control.today') }}</at-button>
                        <at-button size="small" @click="showPopup = false">{{ $t('control.ok') }}</at-button>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
    import moment from 'moment-timezone';

    const DATETIME_FORMAT = 'YYYY-MM-DD HH:mm';

    export default {
        name: 'DatetimeInput',
        props: {
            inputHandler: {
                type: Function,
                required: true,
            },
            value: {
                type: String,
                required: true,
            },
            timezone: {
                type: String,
                required: true,
            },
        },
        data() {
            return {
                showPopup: false,
                datePickerLang: {},
                userTimezone: moment.tz.guess(true),
            };
        },
        computed: {
            datePickerValue() {
                return moment(this.value).add(this.tzDiff).toDate();
            },
            inputValue() {
                return moment(this.value).format(DATETIME_FORMAT);
            },
            hours() {
                const hours = [];
                for (let i = 0; i < 24; i++) {
                    hours.push(i);
                }

                return hours;
            },
            minutes() {
                const minutes = [];
                for (let i = 0; i < 60; i++) {
                    minutes.push(i);
                }

                return minutes;
            },
            hour() {
                return moment(this.value).hours();
            },
            minute() {
                return moment(this.value).minutes();
            },
            tzDiff() {
                return moment().tz(this.timezone, true).diff(moment().tz(this.userTimezone, true)) * -1;
            },
        },
        mounted() {
            window.addEventListener('click', this.hidePopup);

            moment.tz.setDefault(this.timezone);

            const dateTimeStr = this.value
                ? moment(this.value).tz(this.timezone).toISOString()
                : moment().startOf('day').toISOString();
            this.inputHandler(dateTimeStr);
            this.$emit('change', dateTimeStr);
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
            moment.tz.setDefault();
        },
        methods: {
            togglePopup() {
                this.showPopup = !this.showPopup;
            },
            hidePopup(event) {
                if (event.target.closest('.datetimeinput') !== this.$refs.datetimeinput) {
                    this.showPopup = false;
                }
            },
            onDateChange(value) {
                // value = js Date object in user timezone
                const dateTime = moment
                    .utc(value)
                    .tz(this.userTimezone)
                    .hour(this.hour)
                    .minute(this.minute)
                    .tz(this.timezone, true);
                const dateTimeStr = dateTime.toISOString();

                this.inputHandler(dateTimeStr);
                this.$emit('change', dateTimeStr);
            },
            setHour(value) {
                const dateTime = moment(this.value).hour(value);
                const dateTimeStr = dateTime.toISOString();

                this.inputHandler(dateTimeStr);
                this.$emit('change', dateTimeStr);
            },
            setMinute(value) {
                const dateTime = moment(this.value).minute(value);
                const dateTimeStr = dateTime.toISOString();

                this.inputHandler(dateTimeStr);
                this.$emit('change', dateTimeStr);
            },
            disabledDate(date) {
                // date = js Date object in user timezone
                return moment.utc(date).tz(this.userTimezone).tz(this.timezone, true).isAfter(moment(), 'day');
            },
        },
        watch: {
            timezone(newTimezone) {
                let dateTimeStr = this.value
                    ? moment.tz(this.value, newTimezone).toISOString()
                    : moment().startOf('day').toISOString();

                // Subtract one day if selected day is in the future in newTimezone,
                // (relative to user timezone coz calendar show dates in user timezone)
                if (
                    moment
                        .utc(moment.tz(this.value, newTimezone).toISOString())
                        .tz(newTimezone)
                        .tz(this.userTimezone, true)
                        .isAfter(moment().tz(newTimezone), 'day')
                ) {
                    dateTimeStr = this.value
                        ? moment.tz(this.value, newTimezone).subtract(1, 'day').toISOString()
                        : moment().startOf('day').toISOString();
                }

                moment.tz.setDefault(this.timezone);
                this.inputHandler(dateTimeStr);
                this.$emit('change', dateTimeStr);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .datepicker-wrapper {
        position: absolute;
        width: 400px;
        max-height: unset;
    }

    .datepicker__main {
        display: flex;
        flex-flow: row;
        align-items: stretch;

        height: 280px;
    }

    .datepicker__footer {
        display: flex;
        flex-flow: row;
        justify-content: space-between;

        padding: 6px 12px;
    }

    .datepicker {
        flex: 1;
    }

    .datetimeinput::v-deep {
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

    .hour-select,
    .minute-select {
        padding: 5px;
        width: 50px;
        overflow-y: scroll;
        text-align: center;

        .item {
            padding: 3px;
            cursor: pointer;
        }

        .selected {
            background: #2e2ef9;
            color: #ffffff;
            border-radius: 7px;
        }
    }
</style>
