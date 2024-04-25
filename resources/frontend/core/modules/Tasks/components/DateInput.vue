<template>
    <div ref="dateinput" class="dateinput" @click="togglePopup">
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
                    </div>

                    <div class="datepicker__footer">
                        <at-button size="small" @click="onDateChange(null)">{{ $t('tasks.unset_due_date') }}</at-button>
                        <at-button size="small" @click="showPopup = false">{{ $t('control.ok') }}</at-button>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
    import moment from 'moment-timezone';

    const DATETIME_FORMAT = 'YYYY-MM-DD';

    export default {
        name: 'DatetimeInput',
        props: {
            inputHandler: {
                type: Function,
                required: true,
            },
            value: {
                type: String,
                required: false,
            },
        },
        data() {
            return {
                showPopup: false,
                datePickerLang: {},
            };
        },
        computed: {
            datePickerValue() {
                return this.value !== null ? moment(this.value).toDate() : null;
            },
            inputValue() {
                return this.value ? moment(this.value).format(DATETIME_FORMAT) : this.$t('tasks.unset_due_date');
            },
        },
        mounted() {
            window.addEventListener('click', this.hidePopup);

            this.inputHandler(this.value);
            this.$emit('change', this.value);
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
        methods: {
            togglePopup() {
                this.showPopup = !this.showPopup;
            },
            hidePopup(event) {
                if (event.target.closest('.dateinput') !== this.$refs.dateinput) {
                    this.showPopup = false;
                }
            },
            onDateChange(value) {
                const newValue = value !== null ? moment(value).format(DATETIME_FORMAT) : null;
                this.inputHandler(newValue);
                this.$emit('change', newValue);
            },
            disabledDate(date) {
                return false;
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

    .dateinput::v-deep {
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
</style>
