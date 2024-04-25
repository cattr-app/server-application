<template>
    <div class="time-estimate">
        <span class="time-estimate__input-wrapper">
            <at-input-number
                v-model="currentHours"
                :min="0"
                :max="maxHours"
                @blur="handleInputNumber($event, 'currentHours')"
                @change="handleInputNumberChange($event, 'currentHours')"
            ></at-input-number
            ><span class="time-estimate__text"> {{ $i18n.t('field.hour_short') }}.</span>
        </span>
        <span class="time-estimate__input-wrapper">
            <at-input-number
                v-model="currentMinutes"
                :min="0"
                :max="maxMinutes"
                @blur="handleInputNumber($event, 'currentMinutes')"
                @change="handleInputNumberChange($event, 'currentMinutes')"
            ></at-input-number
            ><span class="time-estimate__text"> {{ $i18n.t('field.minute_short') }}.</span>
        </span>
    </div>
</template>

<script>
    import moment from 'moment-timezone';

    const maxUnsignedInt = 4294967295;
    export default {
        name: 'TimeEstimate',
        props: {
            value: {
                type: Number,
                default: 0,
            },
        },
        data() {
            return {
                seconds: [],
                maxHours: Math.floor(maxUnsignedInt / 3600 - 1),
                maxMinutes: 59,
            };
        },
        methods: {
            handleInputNumberChange(seconds, key) {
                this.setValueInSeconds(key, seconds);
            },
            handleInputNumber(ev, key) {
                let number = ev.target.valueAsNumber;
                if (ev.target.max && number > ev.target.max) {
                    number = Number(ev.target.max);
                    ev.target.valueAsNumber = number;
                    ev.target.value = String(number);
                }
                if (ev.target.min && number < ev.target.min) {
                    number = Number(ev.target.min);
                    ev.target.valueAsNumber = number;
                    ev.target.value = String(number);
                }

                this.setValueInSeconds(key, number);
            },
            setValueInSeconds(key, value) {
                let hoursInSeconds = this.currentHours * 3600;
                if (key === 'currentHours') {
                    hoursInSeconds = value * 3600;
                }

                let minutesInSeconds = this.currentMinutes * 60;
                if (key === 'currentMinutes') {
                    minutesInSeconds = value * 60;
                }

                const newTime = hoursInSeconds + minutesInSeconds;
                this.$emit('input', newTime);
            },
        },
        computed: {
            currentHours: {
                get() {
                    return this.hoursAndMinutes.hours;
                },
                set() {},
            },
            currentMinutes: {
                get() {
                    return this.hoursAndMinutes.minutes;
                },
                set() {},
            },
            hoursAndMinutes() {
                const duration = moment.duration(this.value, 'seconds');

                const hours = Math.floor(duration.asHours());
                const minutes = Math.round(duration.asMinutes()) - 60 * hours;

                return {
                    hours,
                    minutes,
                };
            },
            model: {
                get() {
                    return this.value;
                },
                set(option) {
                    this.$emit('input', option);
                },
            },
        },
    };
</script>

<style lang="scss" scoped>
    .time-estimate {
        display: flex;
        column-gap: 1rem;
        &__text {
            padding-left: 5px;
        }
        &__input-wrapper {
            display: flex;
            align-items: flex-end;
        }
        .at-input-number {
            min-width: auto;
            width: 70px;
            padding: 7px 0;
            height: auto;
        }
    }
</style>
